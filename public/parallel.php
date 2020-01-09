<?php
use \parallel\{Runtime, Future, Channel, Events};

/* usage php crawler.php [http://example.com] [workers=8] [limit=500] */

$page    = $argv[1] ?: "https://blog.krakjoe.ninja"; # start crawling this page
$workers = $argv[2] ?: 8;                            # start this number of threads
$limit   = $argv[3] ?: 500;                          # stop at this number of unique pages
$timeout = $argv[4] ?: 3;                            # socket timeout for producers

#############################################################################################
$producer = function(int $worker, int $timeout){
    libxml_use_internal_errors(true);

    ini_set('default_socket_timeout', $timeout);

    $crawling = true;
    $produce  = Channel::open("crawler.production");
    $consume  = Channel::open("crawler.consumption");
    $errors   = Channel::open("management.errors");
    $manager  = Channel::open("management.{$worker}");

    while ($url = $produce->recv()) {
        printf("Producer %ld working %s\n", $worker, $url);

        $html = @file_get_contents($url);

        if (!$html) {
            /* inform manager of errors */
            $errors->send($url);
            continue;
        }

        $consume->send([
            "href" => $url,
            "content" => $html
        ]);

        if ($crawling) {
            $parsed = parse_url($url);

            $docroot = sprintf(
                "%s://%s",
                $parsed["scheme"],
                $parsed["host"]
            );

            $dom = new DOMDocument();
            $dom->loadHTML($html);

            foreach ($dom->getElementsByTagName("a") as $anchor) {
                $href = $anchor->getAttribute("href");

                if (!$href || strpos($href, $docroot) !== 0) {
                    continue;
                }

                /* do management check */
                $manager->send($href);

                if (($result = $manager->recv()) === -1) {
                    /* manager says we hit limits,
                        tell this (or another) producer to shutdown */
                    $produce->send(
                        $crawling = false);
                    break;
                } else {
                    if ($result) {
                        /* allowed to add */
                        $produce->send($href);
                    }
                }
            }
        }
    }

    if ($crawling) {
        /* if still crawling, tell next producer to quit */
        $produce->send(false);
    }

    /* notify a consumer to shutdown,
        this degrades consumers gracefully as producers
        are shutdown */
    $consume->send(false);

    /* notify manager, producer done */
    $manager->close();
};

$consumer = function($worker){
    /* the consumer doesn't do anything, just prints what it got */
    $consume = Channel::open("crawler.consumption");

    while ($result = $consume->recv()) {
        printf("Consumer %ld working on %s with %d bytes\n",
            $worker, $result["href"], strlen($result["content"]));
    }
};

$manager = function(string $page, int $workers, int $limit){
    $events = new Events;
    $index  = [
        $page => true
    ];
    $closing = 0;
    $failing = [];

    /* add error channel */
    $events->addChannel(
        Channel::open("management.errors"));

    /* open and add management channels for producers */
    for ($worker = 0; $worker < $workers; $worker++) {
        $events->addChannel(
            Channel::open("management.{$worker}"));
    }

    foreach ($events as $event) {
        /* we have notification of an error */
        if ($event->source == "management.errors") {
            $failing[
                /* update failing list */
            ] = $event;

            $events->addChannel($event->object);
            continue;
        }

        /* producer closed management channel */
        if ($event->type == Events\Event\Type::Close) {
            if (++$closing == $workers) {
                /* all producers closed,
                    no more errors are coming */
                $events->remove("management.errors");
            }
            continue;
        }

        /* index check */
        if (count($index) == $limit) {
            /* reached limit of index,
                producer will not send any more data */
            $event->object->send(-1);
        } else {
            if (isset($index[$event->value])) {
                /* already exists in index, do not add */
                $event->object->send(false);
            } else {
                /* set in index and allow caller to add */
                $index[
                $event->value
                ] = true;
                $event->object->send(true);
            }
        }

        /* expect another event on this channel */
        $events->addChannel($event->object);
    }

    return ["ok" => count($index), "fail" => count($failing)];
};

$make = function(Closure $closure, array $argv = []) : Future {
    $runtime =
        new Runtime;

    return $runtime->run($closure, $argv);
};

$run = function(string $page, int $workers, int $limit, int $timeout)
use($make, $producer, $consumer, $manager) {
    $produce   = Channel::make("crawler.production", Channel::Infinite);
    $consume   = Channel::make("crawler.consumption");
    $errors    = Channel::make("management.errors", Channel::Infinite);
    $producers = [];
    $consumers = [];
    $managers  = [];
    $events    = new Events;

    $start = microtime(true);

    if ($workers >= $limit) {
        $workers = $limit;
    }

    for ($worker = 0; $worker < $workers; $worker++) {
        /* create management channel */
        $managers[$worker] =
            Channel::make("management.{$worker}");

        /* create producer */
        $producers[$worker] =
            $make($producer, [$worker, $timeout]);

        /* create consumer */
        $consumers[$worker] =
            $make($consumer, [$worker]);

        /* add consumer to event loop */
        $events->addFuture($worker, $consumers[$worker]);
    }

    /* create manager */
    $management =
        $make($manager, [$page, $workers, $limit]);

    /* start */
    $produce->send($page);

    /* wait for consumers to close */
    while ($event = $events->poll());

    /* fetch result from manager */
    $result =
        $management->value();

    printf("Finished with %d pages (%d %s) in %.2f seconds\n",
        $result["ok"],
        $result["fail"],
        $result["fail"] == 1 ?
            "fail" : "failures",
        microtime(true) - $start);
};

$run($page, $workers, $limit, $timeout);
?>
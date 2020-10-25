<?php

declare(strict_types=1);

namespace Chiron\Routing\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Http\MiddlewareQueue;
use Psr\Container\ContainerInterface;
use Chiron\Routing\RoutingHandler;
use Chiron\Pipe\Decorator\RequestHandler\LazyRequestHandler;

final class RoutingHandlerBootloader extends AbstractBootloader
{
    public function boot(MiddlewareQueue $middlewares, ContainerInterface $container): void
    {
        // decorator used as "autowire" (create class & inject the constructor params).
        $handler = new LazyRequestHandler($container, RoutingHandler::class);
        // add the routing handler at the bottom of the middleware stack.
        $middlewares->addMiddleware($handler, MiddlewareQueue::PRIORITY_MIN);
    }
}

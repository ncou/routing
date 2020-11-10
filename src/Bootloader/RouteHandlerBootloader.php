<?php

declare(strict_types=1);

namespace Chiron\Routing\Bootloader;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Http;
use Chiron\Routing\RouteHandler;

final class RouteHandlerBootloader extends AbstractBootloader
{
    public function boot(Http $http): void
    {
        // add the route handler at the bottom end of the pipeline handler.
        $http->setHandler(RouteHandler::class);
    }
}

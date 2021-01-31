<?php

declare(strict_types=1);

namespace Chiron\Routing\Bootloader;

use Chiron\Console\Console;
use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Routing\Command\RouteListCommand;

use Chiron\Routing\Command\ServeCommand;
use Chiron\Routing\Command\ServerStartCommand;
use Chiron\Routing\Command\ServerStatusCommand;
use Chiron\Routing\Command\ServerStopCommand;

final class RoutingCommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {
        $console->addCommand(RouteListCommand::getDefaultName(), RouteListCommand::class);
    }
}

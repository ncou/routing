<?php

declare(strict_types=1);

namespace Chiron\Routing\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Console\Console;
use Chiron\Routing\Command\RouteListCommand;
use Chiron\Routing\Command\ServeCommand;

final class RoutingCommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {
        $console->addCommand(RouteListCommand::getDefaultName(), RouteListCommand::class);
        $console->addCommand(ServeCommand::getDefaultName(), ServeCommand::class);
    }
}

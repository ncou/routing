<?php

declare(strict_types=1);

namespace Chiron\Routing\Facade;

use Chiron\Core\Facade\AbstractFacade;

final class Routes extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return \Chiron\Routing\RouteCollection::class;
    }
}

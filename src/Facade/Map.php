<?php

declare(strict_types=1);

namespace Chiron\Routing\Facade;

use Chiron\Core\Facade\AbstractFacade;

final class Map extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        return \Chiron\Routing\Map::class;
    }
}

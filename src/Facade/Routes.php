<?php

declare(strict_types=1);

namespace Chiron\Routing\Facade;

use Chiron\Core\Facade\AbstractFacade;

final class Routes extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        return \Chiron\Routing\RouteCollection::class;
    }
}

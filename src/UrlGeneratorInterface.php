<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\UriInterface;

interface UrlGeneratorInterface
{
    public function absoluteUrlFor(UriInterface $uri, string $routeName, array $substitutions = [], array $queryParams = []): string;

    public function relativeUrlFor(string $routeName, array $substitutions = [], array $queryParams = []): string;
}

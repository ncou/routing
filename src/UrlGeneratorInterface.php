<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\UriInterface;

// TODO : ajouter la phpDoc !!!!
// TODO : on a jouté dans les paramétre de la méthode les $queryParams mais il faudrait aussi gérer les segments. ex : "/posts/search?foo=bar#first"
interface UrlGeneratorInterface
{
    public function absoluteUrlFor(UriInterface $uri, string $routeName, array $substitutions = [], array $queryParams = []): string;

    public function relativeUrlFor(string $routeName, array $substitutions = [], array $queryParams = []): string;
}

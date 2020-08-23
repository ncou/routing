<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Chiron\Routing\Traits\MiddlewareAwareInterface;
use Chiron\Routing\Traits\StrategyAwareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;

//https://github.com/yiisoft/router/blob/master/src/UrlGeneratorInterface.php
//https://github.com/slimphp/Slim/blob/cf68c2dede23b2c05ea9162379bf10ba6c913331/Slim/Routing/RouteParser.php#L112
interface UrlGeneratorInterface
{


    //public function generate(string $routePath, array $substitutions = [], array $queryParams = []): string;
    //public function fullUrlFor(UriInterface $uri, string $routeName, array $substitutions = [], array $queryParams = []): string;

    public function absoluteUrlFor(UriInterface $uri, string $routeName, array $substitutions = [], array $queryParams = []): string;

    //public function urlFor(string $routeName, array $substitutions = [], array $queryParams = []): string;

    public function relativeUrlFor(string $routeName, array $substitutions = [], array $queryParams = []): string;



}

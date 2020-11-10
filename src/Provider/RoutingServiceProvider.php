<?php

declare(strict_types=1);

namespace Chiron\Routing\Provider;

use Chiron\Core\Container\Provider\ServiceProviderInterface;
use Chiron\Container\BindingInterface;
use Chiron\Container\Container;
use Chiron\Http\Factory\ResponseFactory;
use Http\Factory\Psr17FactoryFinder;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

use Psr\Http\Message\ServerRequestInterface;
use Chiron\Routing\Route;
use Chiron\Routing\MatchingResult;

use Chiron\Core\Exception\ScopeException;
use Closure;

/**
 * Chiron routing services provider.
 */
// TODO : renommer en "RouteServiceProvider::class"
class RoutingServiceProvider implements ServiceProviderInterface
{
    /**
     * Register Chiron routing services.
     *
     * @param Container $container
     */
    public function register(BindingInterface $container): void
    {
        // This SHOULDN'T BE a singleton(), use a basic bind() to ensure Request instance is fresh !
        $container->bind(Route::class, Closure::fromCallable([$this, 'route']));
    }

    private function route(ServerRequestInterface $request): Route
    {
        $route = $request->getAttribute(Route::ATTRIBUTE);

        if ($route === null) {
            throw new ScopeException('Unable to resolve Route, invalid request scope.');
        }

        return $route;
    }
}

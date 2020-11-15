<?php

declare(strict_types=1);

namespace Chiron\Routing\Provider;

use Chiron\Container\BindingInterface;
use Chiron\Container\Container;
use Chiron\Core\Container\Provider\ServiceProviderInterface;
use Chiron\Core\Exception\ScopeException;
use Chiron\Routing\Route;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Chiron\Http\Config\HttpConfig;
use Chiron\Routing\RouteCollection;

/**
 * Chiron Routing services provider.
 */
class RoutingServiceProvider implements ServiceProviderInterface
{
    /**
     * Register Chiron routing services.
     *
     * @param BindingInterface $binder
     */
    public function register(BindingInterface $binder): void
    {
        // This SHOULDN'T BE a singleton(), use a basic bind() to ensure Request instance is fresh !
        $binder->bind(Route::class, Closure::fromCallable([$this, 'route']));
        // This should be a singleton, because the route collection could be updated during app bootloading.
        $binder->singleton(RouteCollection::class, Closure::fromCallable([$this, 'routeCollection']));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws ScopeException If the attribute is not found in the request.
     *
     * @return Route
     */
    private function route(ServerRequestInterface $request): Route
    {
        $route = $request->getAttribute(Route::ATTRIBUTE);

        if ($route === null) {
            throw new ScopeException('Unable to resolve Route, invalid request scope.');
        }

        return $route;
    }

    /**
     * @param Container $container
     * @param HttpConfig $config
     *
     * @return RouteCollection
     */
    private function routeCollection(Container $container, HttpConfig $config): RouteCollection
    {
        return new RouteCollection($container, $config->getBasePath());
    }
}

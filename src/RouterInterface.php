<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{

    /**
     * Match a request uri with a Route pattern.
     *
     * @param ServerRequestInterface $request
     *
     * @return MatchingResult
     *
     * @throws Exception\RouterException If an internal problem occured
     */
    public function match(ServerRequestInterface $request): MatchingResult;

    /**
     * Add a Route to the collection, and return the route for chaining calls.
     *
     * @param Route $route
     *
     * @return MatchingResult
     */
    public function addRoute(Route $route): Route;

    /**
     * Get a named route.
     *
     * @param string $name Route name
     *
     * @throws Exception\RouteNotFoundException If named route does not exist
     *
     * @return \Chiron\Router\Route
     */
    public function getNamedRoute(string $name): Route;

    /**
     * Get route objects.
     *
     * @return Route[]
     */
    public function getRoutes(): array;

    /**
     * Set the base path for each Route.
     * Useful if you are running your application from a subdirectory.
     */
    //public function setBasePath(string $basePath): void;

    /**
     * Get the router base path.
     */
    //public function getBasePath(): string;

    // TODO : réflaichir si on doit ajouter la méthode : addGroup dans cette interface.
    // TODO : réflaichir si on doit ajouter la méthode : generateUri dans cette interface.
    // TODO : réflaichir si on doit ajouter la méthode : getRoutes dans cette interface.
    // TODO : réflaichir si on doit ajouter la méthode : getNamedRoute(string $name) ou en plus court getRoute(string $name) dans cette interface.
    // TODO : réflaichir si on doit ajouter la méthode : removeNamedRoute dans cette interface.
}

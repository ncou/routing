<?php

declare(strict_types=1);

namespace Chiron\Routing\Event;

use Chiron\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * RouteMatchedEvent event is raised if the route matched.
 */
final class RouteMatchedEvent
{
    /** @var Route */
    private $route;
    /** @var ServerRequestInterface */
    private $request;

    /**
     * @param Route $route
     * @param ServerRequestInterface $request
     */
    public function __construct(Route $route, ServerRequestInterface $request)
    {
        $this->route = $route;
        $this->request = $request;
    }

    /**
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}

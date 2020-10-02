<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Http\Message\RequestMethod;

// TODO : regarder ici : https://github.com/l0gicgate/Slim/blob/4.x-DispatcherResults/Slim/DispatcherResults.php
//https://github.com/slimphp/Slim/blob/4.x/Slim/Routing/RoutingResults.php
//https://github.com/yiisoft/router/blob/master/src/MatchingResult.php

/**
 * Value object representing the results of routing.
 *
 * RouterInterface::match() is defined as returning a MatchingResult instance,
 * which will contain the following state:
 *
 * - isSuccess()/isFailure() indicate whether routing succeeded or not.
 * - On success, it will contain:
 *   - the matched route name (typically the path)
 *   - the matched route middleware
 *   - any parameters matched by routing
 * - On failure:
 *   - isMethodFailure() further qualifies a routing failure to indicate that it
 *     was due to using an HTTP method not allowed for the given path.
 *   - If the failure was due to HTTP method negotiation, it will contain the
 *     list of allowed HTTP methods.
 *
 * MatchingResult instances are consumed by the Application in the routing
 * middleware.
 */
class MatchingResult implements RequestHandlerInterface
{
    /**
     * @var string
     */
    public const ATTRIBUTE = '__MatchingResult__';
    /**
     * @var null|string[]
     */
    private $allowedMethods = [];

    /**
     * @var array
     */
    private $matchedParams = [];

    /**
     * @var string
     */
    private $matchedRouteName;

    /**
     * @var array
     */
    private $matchedRouteMiddlewareStack;

    /**
     * Route matched during routing.
     *
     * @var Route
     */
    private $route;

    /**
     * @var bool success state of routing
     */
    private $success;

    /**
     * Only allow instantiation via factory methods (static::fromRoute or static::fromRouteFailure).
     */
    private function __construct()
    {
    }

    /**
     * Create an instance representing a route succes from the matching route.
     *
     * @param array $params parameters associated with the matched route, if any
     */
    public static function fromRoute(Route $route, array $params = []): self
    {
        $result = new self();
        $result->success = true;
        $result->route = $route;
        $result->matchedParams = $params;

        return $result;
    }

    /**
     * Create an instance representing a route failure.
     *
     * @param array $methods HTTP methods allowed for the current URI.
     */
    public static function fromRouteFailure(array $methods): self
    {
        $result = new self();
        $result->success = false;
        $result->allowedMethods = $methods;

        return $result;
    }

    /**
     * Does the result represent successful routing?
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Is this a routing failure result? (could be a not found failure or an invalid method failure)
     */
    public function isFailure(): bool
    {
        return ! $this->success;
    }

    /**
     * Does the result represent failure to route due to HTTP method?
     */
    public function isMethodFailure(): bool
    {
        return $this->isFailure() && $this->allowedMethods !== RequestMethod::ANY;
    }

    /**
     * Retrieve the route that resulted in the route match.
     *
     * @return false|null|Route false if representing a routing failure;
     *                          null if not created via fromRoute(); Route instance otherwise
     */
    // TODO : méthode à virer elle ne sert à rien !!!!
    public function getMatchedRoute()
    {
        return $this->isFailure() ? false : $this->route;
    }

    /**
     * Retrieve the matched route name, if possible.
     *
     * If this result represents a failure, return false; otherwise, return the
     * matched route name.
     *
     * @return false|string
     */
    // TODO : méthode à virer elle ne sert à rien !!!!
    public function getMatchedRouteName()
    {
        if ($this->isFailure()) {
            return false;
        }
        if (! $this->matchedRouteName && $this->route) {
            $this->matchedRouteName = $this->route->getName();
        }

        return $this->matchedRouteName;
    }

    /**
     * Retrieve all the middlewares, if possible.
     *
     * If this result represents a failure, return false; otherwise, return the
     * middleware stack of the Route.
     *
     * @return false|array
     */
    // TODO : méthode à virer elle ne sert à rien !!!!
    public function getMatchedRouteMiddlewareStack()
    {
        if ($this->isFailure()) {
            return false;
        }

        if (! $this->matchedRouteMiddlewareStack && $this->route) {
            $this->matchedRouteMiddlewareStack = $this->route->getMiddlewareStack();
        }

        return $this->matchedRouteMiddlewareStack;
    }

    /**
     * Returns the matched params.
     *
     * Guaranted to return an array, even if it is simply empty.
     */
    public function getMatchedParams(): array
    {
        return $this->matchedParams;
    }

    /**
     * Retrieve the allowed methods for the route failure.
     *
     * @return string[] HTTP methods allowed
     */
    public function getAllowedMethods(): array
    {
        if ($this->isSuccess()) {
            return $this->route
                ? $this->route->getAllowedMethods()
                : [];
        }

        return $this->allowedMethods;
    }

    // TODO : lever une exception si on execute ce bout de code sans que le résultat soit à "isSuccess === true"
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Merge the default values defined in the Route with the parameters, and add the request class name used to resole the callable parameters using type hint.
        //$params = array_merge($this->route->getDefaults(), $this->matchedParams, [ServerRequestInterface::class => $request]);
        $params = array_merge($this->route->getDefaults(), $this->matchedParams);

        // Inject individual matched parameters in the Request.
        foreach ($params as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        $handler = new RequestHandler();

        foreach ($this->route->getMiddlewareStack() as $middleware) {
            $handler->pipe($middleware);
        }

        // the fallback handler could be null if the last middleware attached to the route return a response.
        if ($this->route->getHandler() !== null) {
            $handler->setFallback($this->route->getHandler());
        }

        return $handler->handle($request);
    }
}

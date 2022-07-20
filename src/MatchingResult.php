<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Chiron\Http\Message\RequestMethod as Method;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

//https://github.com/yiisoft/router/blob/4a762f14c9e338e94fc27dd3768b45712409ae4a/src/MatchingResult.php

//https://github.com/yiisoft/router/blob/master/src/MatchingResult.php
//https://github.com/yiisoft/router/blob/master/src/Middleware/Router.php#L42
//TODO : better Marshal function :      https://github.com/yiisoft/router-fastroute/blob/aeb479766daef3e97b61bf1fbbba78a8c0f41330/src/UrlMatcher.php#L257

// TODO : regarder ici : https://github.com/l0gicgate/Slim/blob/4.x-DispatcherResults/Slim/DispatcherResults.php
//https://github.com/slimphp/Slim/blob/4.x/Slim/Routing/RoutingResults.php

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
// TODO : renommer en RoutingResults ???
final class MatchingResult implements RequestHandlerInterface
{
    public const ATTRIBUTE = '__MatchingResult__';
    /**
     * @var string[]|null
     */
    private $allowedMethods = [];

    /**
     * @var array
     */
    private $matchedParameters = [];

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
     * @param array $parameters parameters associated with the matched route, if any
     */
    public static function fromRoute(Route $route, array $parameters): self
    {
        $result = new self();
        $result->success = true;
        $result->route = $route;
        $result->matchedParameters = $parameters;

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
        return $this->isFailure() && $this->allowedMethods !== Method::ANY;
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

    /**
     * Retrieve the route that resulted in the route match.
     *
     * @return false|Route|null false if representing a routing failure;
 * null if not created via fromRoute(); Route instance otherwise
     */
    // TODO : ne pas avoir cette méthode en public car elle ne sera pas utilisée en dehors de cette classe !!!
    // TODO : renvoyer plutot null si c'est un failure ca sera plus propre d'avoir un return type ?Route plutot que rien du tout !!!!
    // TODO : c'est possible d'avoir un type de retour à null ???
    public function getMatchedRoute()
    {
        return $this->isFailure() ? false : $this->route;
    }

    /**
     * Returns the matched parameters.
     *
     * Guaranted to return an array, even if it is simply empty.
     */
    // TODO : ne pas avoir cette méthode en public car elle ne sera pas utilisée en dehors de cette classe !!! => Faux car on pourrait créer un helper pour récupérer les parametres de la route pour faciliter la tache à l'utilisateur !!! https://github.com/irazasyed/larasupport/blob/11fc641af48c2b5c92fb4400bb628e71222456ef/src/helpers.php#L5
    public function getMatchedParameters(): array
    {
        return $this->matchedParameters;
    }

    /**
     * Store the matched route parameters in the request and execute the route handler.
     * Request attributes are be used by the Injector for the route handler parameters resolution.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Inject individual matched parameters in the Request attributes.
        foreach ($this->matchedParameters as $parameter => $value) {
            $request = $request->withAttribute($parameter, $value);
        }

        return $this->route->handle($request);
    }
}

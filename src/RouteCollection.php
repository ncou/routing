<?php

declare(strict_types=1);

namespace Chiron\Routing;

use ArrayIterator;
use Chiron\Container\Container;
use Chiron\Container\SingletonInterface;
use Chiron\Routing\Controller\RedirectController;
use Chiron\Routing\Controller\ViewController;
use Chiron\Routing\Exception\RouteNotFoundException;
use Chiron\Routing\Exception\RouterException;
use Countable;
use IteratorAggregate;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Http\Message\RequestMethod as Method;
use Chiron\Http\Message\StatusCode as Status;
use Psr\Http\Message\UriInterface;

// HEAD Support :
// https://github.com/atanvarno69/router
// https://github.com/slimphp/Slim/blob/4.x/Slim/Routing/FastRouteDispatcher.php#L36

//https://github.com/fratily/router/blob/master/src/RouteCollector.php

// GROUP
//https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteCreator.php#L89
//https://github.com/auraphp/Aura.Router/blob/3.x/src/Map.php#L373
//https://github.com/nikic/FastRoute/blob/master/src/RouteCollector.php#L47
// Group / Prefix : https://github.com/mrjgreen/phroute/blob/master/src/Phroute/RouteCollector.php#L185
// Group : https://github.com/atanvarno69/router/blob/master/src/SimpleRouter.php#L91

// RouteData une sorte de proxy pour ajouter certaines infos à la route
//https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteData.php

/**
 * Aggregate routes for the router.
 *
 * This class provides * methods for creating path+HTTP method-based routes and
 * injecting them into the router:
 *
 * - get
 * - post
 * - put
 * - patch
 * - delete
 * - any
 *
 * A general `route()` method allows specifying multiple request methods and/or
 * arbitrary request methods when creating a path-based route.
 *
 * Internally, the class performs some checks for duplicate routes when
 * attaching via one of the exposed methods, and will raise an exception when a
 * collision occurs.
 */
// TODO : ajouter une méthode pour définir un basePath genre ->withBasePath(string $path) qui retourne un clone de la classe ou alors simplement un ->setBasePath() ou ->setPrefix()
// TODO : ajouter la gestion des "Group"
// TODO : ajouter une méthode addRoute(Route $route) et une autre addRoutes(array $routes) pour injecter des objets Route directement dans le router !!!!
// TODO : ajouter une méthode "getRoutes" ????
// TODO : harmoniser les termes et utiliser le terme "path" pour toute les variables (c'est à dire remplacer $pattern et $url par $path dans cette classe.) Faire la même chose dans le classe "Route"
// TODO : déplacer cette classe + les deux classes ViewController et RedirectController ainsi que la facade directement dans un répertoire "Routing" qui serait dans le framework Chiron !!!!
// TODO : utiliser les constantes de la classe Methode (ex : Method::POST / ::TRACE  ...etc)
// TODO : harmoniser le terme "pattern" versus "path" qui est différent entre les classes Route et RouteCollector. Idem pour la fonction "map()" qui n'a pas la même signature entre les deux classes.

// TODO : créer une méthode globale nommée "base_path(): string" qui se chargerai de retourner la valeur du getBasePath() de cette classe (qu'on irait chercher via le container) ???

// TODO : attention si on garde l'interface SingletonInterface il faut ajouter une dépendance sur le Container, il faudrait plutot créer une classe ServiceProvider dans ce package qui se chargerai faire un bind singleton pour la classe RouteCollector. Il faudrait surement aussi binder la classe Pipeline avec une instance initialisée avec un setFallback qui pointe sur la classe RoutingHandler
// TODO : utiliser des classes de constantes pour les headers, status code et autre méthodes (GET/POST/PUT...etc)
// TODO : ajouter une fonction pour gérer les group, soit on l'appelle 'prefix()' ou 'mount()' ou 'group()' https://github.com/symfony/routing/blob/5.x/RouteCollectionBuilder.php#L117
// TODO : générer un nom unique pour la route ??? https://github.com/symfony/routing/blob/5.x/RouteCollectionBuilder.php#L316
// TODO : il faudrait rajouter un contrôle sur les doublons de "name" ??? car cela peut poser soucis (notamment si on souhaite générer une url pour une route nommée) !!!!
// TODO : harmoniser la signature de la méthode 'map()' avec celle de la classe Route qui contient aussi une méthode statique "map()" mais on peut lui passer un tableau de méthodes en second paramétre.
/**
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
 */
final class RouteCollection implements Countable, IteratorAggregate
{
    /** @var Route[] List of all routes registered directly with the application. */
    private $routes = [];
    /** @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host) */
    private $prefix;
    /** @var Container */
    private $container;

    /**
     * @param Container $container
     * @param string    $basePath Useful if you are running your application from a subdirectory.
     */
    public function __construct(Container $container, string $basePath = '/')
    {
        $this->container = $container;
        $this->prefix = trim(trim($basePath), '/'); // TODO : il faudrait pas remonter ce bout de code [les différents trim()] directement dans la méthode httpConfig->getBasePath() ????
    }

    /**
     * Add GET route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.1
     * @see https://tools.ietf.org/html/rfc2616#section-9.3
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function get(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::GET);
    }

    /**
     * Add HEAD route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.2
     * @see https://tools.ietf.org/html/rfc2616#section-9.4
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function head(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::HEAD);
    }

    /**
     * Add POST route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.3
     * @see https://tools.ietf.org/html/rfc2616#section-9.5
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function post(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::POST);
    }

    /**
     * Add PUT route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.4
     * @see https://tools.ietf.org/html/rfc2616#section-9.6
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function put(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::PUT);
    }

    /**
     * Add DELETE route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.5
     * @see https://tools.ietf.org/html/rfc2616#section-9.7
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function delete(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::DELETE);
    }

    /**
     * Add OPTIONS route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.7
     * @see https://tools.ietf.org/html/rfc2616#section-9.2
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function options(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::OPTIONS);
    }

    /**
     * Add TRACE route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.8
     * @see https://tools.ietf.org/html/rfc2616#section-9.8
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function trace(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::TRACE);
    }

    /**
     * Add PATCH route.
     *
     * @see http://tools.ietf.org/html/rfc5789
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function patch(string $pattern): Route
    {
        return $this->map($pattern)->method(Method::PATCH);
    }

    /**
     * Add route for any HTTP method.
     * Supports the following methods : 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'.
     *
     * @param string $pattern The route URI pattern
     *
     * @return Route
     */
    public function any(string $pattern): Route
    {
        return $this->map($pattern);
    }

    /**
     * Adds a route and returns it for future modification.
     * By default the Route will support all the methods : 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'.
     *
     * @param string          $pattern
     * @param RequestHandlerInterface $handler
     *
     * @return Route
     */
    public function map(string $pattern): Route
    {
        $route = new Route($pattern);
        $this->addRoute($route);

        return $route;
    }

    /**
     * Add a Route to the collection and 'inject' the container.
     * The Route pattern is also updated to include the 'basepath'.
     *
     * @param Route $route
     *
     * @return $this
     */
    public function addRoute(Route $route): self
    {
        // Add the container if not already presents in the Route instance.
        if (! $route->hasContainer()) {
            $route->setContainer($this->container);
        }

        // Update the route path to append the routecollection prefix.
        $pattern = '/' . $this->prefix . $route->getPath();
        $route->setPath($pattern);

        $this->routes[] = $route;

        return $this;
    }

    /**
     * Check if the named route exists.
     *
     * @param string $name Route name.
     *
     * @return bool
     */
    public function hasRoute(string $name): bool
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a named route.
     *
     * @param string $name Route name.
     *
     * @throws RouteNotFoundException If named route does not exist.
     *
     * @return Route
     */
    public function getRoute(string $name): Route
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        throw new RouteNotFoundException($name);
    }

    /**
     * Create a permanent redirect from one URI to another (status code = 301).
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/301
     * @see https://tools.ietf.org/html/rfc7231#section-6.4.2
     *
     * @param UriInterface|string $uri
     * @param string $destination
     *
     * @throws RouterException If the $uri parameter is not valid.
     *
     * @return Route
     */
    public function permanentRedirect($uri, string $destination): Route
    {
        return $this->redirect($uri, $destination, Status::MOVED_PERMANENTLY);
    }

    /**
     * Create a redirect from one URI to another (status code = 302).
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/302
     * @see https://tools.ietf.org/html/rfc7231#section-6.4.3
     *
     * @param UriInterface|string $uri
     * @param string $destination
     * @param int    $status
     *
     * @throws RouterException If the $uri parameter is not valid.
     *
     * @return Route
     */
    public function redirect($uri, string $destination, int $status = Status::FOUND): Route
    {
        if (! is_string($uri) && ! $uri instanceof UriInterface) {
            throw new RouterException('Redirection allowed only for string or UriInterface uris.');
        }

        $controller = [RedirectController::class, 'redirect'];

        return $this->map((string) $uri)
                ->to($controller)
                ->setDefault('destination', $destination)
                ->setDefault('status', $status);
    }

    /**
     * Register a new route that returns a view.
     *
     * @param UriInterface|string $uri
     * @param string $template
     * @param array  $parameters
     *
     * @throws RouterException If the $uri parameter is not valid.
     *
     * @return Route
     */
    public function view($uri, string $template, array $params = []): Route
    {
        if (! is_string($uri) && ! $uri instanceof UriInterface) {
            throw new RouterException('View rendering allowed only for string or UriInterface uris.');
        }

        $controller = [ViewController::class, 'view'];

        return $this->map((string) $uri)
                ->to($controller)
                ->method(Method::GET, Method::HEAD)
                ->setDefault('template', $template)
                ->setDefault('parameters', $params);
    }

    /**
     * Create a route group with a common prefix.
     *
     * All routes created in the passed callback will have the given prefix prepended.
     *
     * @param string $template
     * @param array  $parameters
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $this->prefix = $previousPrefix . rtrim(trim($prefix), '/');

        $callback($this);

        $this->prefix = $previousPrefix;
    }

    /**
     * Get all route objects.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->routes);
    }
}

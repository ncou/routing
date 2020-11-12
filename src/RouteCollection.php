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
/**
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
 */
final class RouteCollection implements Countable, IteratorAggregate, SingletonInterface
{
    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    private $basePath = '/';

    /**
     * List of all routes registered directly with the application.
     *
     * @var Route[]
     */
    private $routes = [];

    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     */
    // TODO : tester cette méthode et réfléchir à comment on trim de slash '/' (plutot à gauche ou droite ou les deux ????).
    public function setBasePath(string $basePath): void
    {
        //$this->basePath = rtrim($basePath, '/');
        //$this->basePath = $basePath;
        //$this->basePath = '/' . ltrim($basePath, '/');

        $this->basePath = sprintf('/%s', ltrim($basePath, '/'));
    }

    /**
     * Get the router base path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
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
    // TODO : harmoniser la signature de la méthode avec la classe Route qui contient aussi une méthode statique "map()" mais on peut lui passer un tableau de méthodes en second paramétre.
    // TODO : renommer cette méthode en 'add()' ??? https://github.com/symfony/routing/blob/5.x/RouteCollectionBuilder.php#L95
    public function map(string $pattern): Route
    {
        // TODO : ATTENTION !!!! déplacer cette méthode dans le "addRoute" et utiliser un appel au Route->setPath() pour mettre à jour le path. Car dans le cas ou on crée manuellement une route et qu'on l'ajoute ensuite au RouteCollector qui a été configuré avec un basePath, celui-ci ne sera pas appliqué lorsqu'on ajoutera la Route !!!!
        // TODO : attention on va avoir un bug, dans le cas ou on ajoute une Route manuellement via addRoute (ou même via map()) et qu'on modifie ensuite le basePath, il ne sera pas appliqué aux routes car il n'y a pas d'update du Route->setPath() lorsuq'on ajoute un basePath (via setBasePath()). => Solution : mettre le basePath en paramétre du constructeur et virer la méthode setBasePath, comme ca on n'a plus ce risque de bugs !!!! Ou alors dans la méthode setBasePath on boucle sur les routes pour ajouter le basepath au path de la Route !!! https://github.com/symfony/routing/blob/5.x/RouteCollection.php#L157
        $pattern = rtrim($this->basePath, '/') . '/' . ltrim($pattern, '/'); // TODO : vérifier les différents trims, pour pas que cela soit redondant avec le code dans la classe Route::__constructor($path)!!! https://github.com/symfony/routing/blob/5.x/RouteCollectionBuilder.php#L270

        $route = new Route($pattern);

        $this->addRoute($route);

        return $route;
    }

    /**
     * Add a Route to the collection and 'inject' the container.
     *
     * @param Route $route
     *
     * @return $this
     */
    public function addRoute(Route $route): self
    {
        $route->setContainer($this->container);
        $this->routes[] = $route;

        return $this;
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
     * Get a named route.
     *
     * @param string $name Route name
     *
     * @throws RouteNotFoundException If named route does not exist
     *
     * @return Route
     */
    // TODO : il faudrait rajouter un contrôle sur les doublons de "name" ??? car cela peut poser soucis (notamment si on souhaite générer une url pour une route nommée) !!!!
    // TODO : renommer la méthode en getRoute() ????
    // TODO : supprimer la classe RouteNotFoundException du package pour utiliser l'exception générique RouterException ????
    // TODO : réfléchir si il faut lever une exception ou alors simplement retourner null si la route n'existe pas !!!
    public function getNamedRoute(string $name): Route
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        throw new RouteNotFoundException($name);
    }

    /**
     * Get route objects.
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

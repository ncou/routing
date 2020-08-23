<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Chiron\Routing\Middleware\RoutingMiddleware;

use Chiron\Routing\Route;
use Chiron\Routing\RouteGroup;
use Psr\Http\Server\RequestHandlerInterface;

use Chiron\Routing\Controller\RedirectController;
use Chiron\Routing\Controller\ViewController;
use Chiron\Routing\Target\TargetFactory;

use Chiron\Container\SingletonInterface;

//https://github.com/fratily/router/blob/master/src/RouteCollector.php

// GROUP
//https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteCreator.php#L89
//https://github.com/auraphp/Aura.Router/blob/3.x/src/Map.php#L373
//https://github.com/nikic/FastRoute/blob/master/src/RouteCollector.php#L47

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
final class RouteCollector implements SingletonInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TargetFactory
     */
    private $target;

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    private $basePath;

    /**
     * List of all routes registered directly with the application.
     *
     * @var Route[]
     */
    private $routes = [];

    public function __construct(RouterInterface $router, TargetFactory $target)
    {
        $this->router = $router;
        $this->target = $target;
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
     * Get a named route (proxy helper).
     *
     * @param string $name Route name
     *
     * @throws Exception\RouteNotFoundException If named route does not exist
     *
     * @return \Chiron\Routing\Route
     */
    public function getNamedRoute(string $name): Route
    {
        return $this->router->getNamedRoute($name);
    }

    /**
     * Get route objects (proxy helper).
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->router->getRoutes();
    }

    /**
     * Add a route for the router to match.
     *
     * @param string          $path
     * @param RequestHandlerInterface $handler
     *
     * @return \Chiron\Routing\Route
     */
    // TODO : harmoniser la signature de la méthode avec la classe Route qui contient aussi une méthode statique "map()" mais on peut lui passer un tableau de méthodes en second paramétre.
    public function map(string $path): Route
    {
        $path = rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
        $route = new Route($path);

        $this->routes[] = $route;
        $this->router->addRoute($route);

        return $route;
    }

    /**
     * Add GET route. Also add the HEAD method because if you can do a GET request, you can also implicitly do a HEAD request.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.1
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.3
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function get(string $pattern): Route
    {
        return $this->map($pattern)->method('GET');
    }

    /**
     * Add HEAD route.
     *
     * HEAD was added to HTTP/1.1 in RFC2616
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.2
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function head(string $pattern): Route
    {
        return $this->map($pattern)->method('HEAD');
    }

    /**
     * Add POST route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.3
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.5
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function post(string $pattern): Route
    {
        return $this->map($pattern)->method('POST');
    }

    /**
     * Add PUT route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.4
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.6
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function put(string $pattern): Route
    {
        return $this->map($pattern)->method('PUT');
    }

    /**
     * Add DELETE route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.5
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.7
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function delete(string $pattern): Route
    {
        return $this->map($pattern)->method('DELETE');
    }

    /**
     * Add OPTIONS route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.7
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.2
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function options(string $pattern): Route
    {
        return $this->map($pattern)->method('OPTIONS');
    }

    /**
     * Add TRACE route.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.8
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.8
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function trace(string $pattern): Route
    {
        return $this->map($pattern)->method('TRACE');
    }

    /**
     * Add PATCH route.
     *
     * PATCH was added to HTTP/1.1 in RFC5789
     *
     * @see http://tools.ietf.org/html/rfc5789
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function patch(string $pattern): Route
    {
        return $this->map($pattern)->method('PATCH');
    }

    /**
     * Add route for any HTTP method.
     * Supports the following methods : 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'.
     *
     * @param string          $pattern The route URI pattern
     * @param RequestHandlerInterface $handler The route callback routine
     *
     * @return \Chiron\Routing\Route
     */
    public function any(string $pattern): Route
    {
        return $this->map($pattern);
    }

    /**
     * Create a permanent redirect from one URI to another.
     *
     * @param string $url
     * @param string $destination
     *
     * @return \Chiron\Routing\Route
     */
    public function permanentRedirect(string $url, string $destination): Route
    {
        return $this->redirect($url, $destination, 301);
    }

    /**
     * Create a redirect from one URI to another.
     *
     * @param string $url
     * @param string $destination
     * @param int    $status
     *
     * @return \Chiron\Routing\Route
     */
    // TODO : permettre de passer un UriInterface ou une string pour la destination !!!
    public function redirect(string $url, string $destination, int $status = 302): Route
    {
        $controller = $this->target->callback([RedirectController::class, 'redirect']);

        return $this->map($url)
                ->to($controller)
                ->setDefault('destination', $destination)
                ->setDefault('status', $status);
    }

    /**
     * Register a new route that returns a view.
     *
     * @param string $url
     * @param string $template
     * @param array  $parameters
     *
     * @return \Chiron\Routing\Route
     */
    public function view(string $url, string $template, array $params = []): Route
    {
        $controller = $this->target->callback([ViewController::class, 'view']);

        return $this->map($url)
                ->to($controller)
                ->method('GET', 'HEAD')
                ->setDefault('template', $template)
                ->setDefault('parameters', $params);
    }

    /**
     * Retrieve all directly registered routes inside this collector.
     *
     * @return Route[]
     */
    // TODO : réfléchir si on garde cette méthode, qui ne semble pas servir à grand chose... Elle devrait plutot se trouver dans le Router car si on passe directement par le router pour ajouter une route sans passer par le collector on ne la trouvera pas dans cette liste, et lorsqu'on va ajouter la notion de group dans le collector on va passer au callback la classe collector donc cette méthode getRoutes va poluer l'objet...
    /*
    public function getRoutes() : array
    {
        return $this->routes;
    }*/

    /**
     * Determine if the route is duplicated in the current list.
     *
     * Checks if a route with the same name or path exists already in the list;
     * if so, and it responds to any of the $methods indicated, raises
     * a DuplicateRouteException indicating a duplicate route.
     *
     * @throws Exception\DuplicateRouteException on duplicate route detection.
     */
    // TODO : méthode à virer
    /*
    private function checkForDuplicateRoute(string $path, array $methods = null) : void
    {
        if (null === $methods) {
            $methods = Route::HTTP_METHOD_ANY;
        }

        $matches = array_filter($this->routes, function (Route $route) use ($path, $methods) {
            if ($path !== $route->getPath()) {
                return false;
            }

            if ($methods === Route::HTTP_METHOD_ANY) {
                return true;
            }

            return array_reduce($methods, function ($carry, $method) use ($route) {
                return ($carry || $route->allowsMethod($method));
            }, false);
        });

        if (! empty($matches)) {
            $match = reset($matches);
            $allowedMethods = $match->getAllowedMethods() ?: ['(any)'];
            $name = $match->getName();
            throw new Exception\DuplicateRouteException(sprintf(
                'Duplicate route detected; path "%s" answering to methods [%s]%s',
                $match->getPath(),
                implode(',', $allowedMethods),
                $name ? sprintf(', with name "%s"', $name) : ''
            ));
        }
    }*/

}

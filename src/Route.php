<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Chiron\Container\Container;
use Chiron\Container\ContainerAwareInterface;
use Chiron\Container\ContainerAwareTrait;
use Chiron\Http\Message\RequestMethod as Method;
use Chiron\Http\Traits\PipelineTrait;
use Chiron\Routing\Exception\RouteException;
use Chiron\Routing\Target\TargetInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Chiron\Event\EventDispatcherAwareInterface;
use Chiron\Event\EventDispatcherAwareTrait;

use Chiron\Routing\Event\RouteMatchedEvent;

// TODO : Extract name for route : https://github.com/getsentry/sentry-laravel/blob/5e869deaef12da8a626ee2b0aa1b4bf15869a6cc/src/Sentry/Laravel/Integration.php#L126

//https://github.com/symfony/routing/blob/master/Route.php

// Ajouter les middleware comme des options :    https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteConfigureTrait.php#L272
// ajouter des extra dans la route :  https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteConfigureTrait.php#L353
// et l'utilitaire pour faire un deep merge (mergeRecursive) :   https://github.com/ventoviro/windwalker-utilities/blob/master/Arr.php#L803
// et le bout de code pour récupérer les extra : https://github.com/ventoviro/windwalker/blob/8b1aba30967dd0e6c4374aec0085783c3d0f88b4/src/Router/Route.php#L515

// TODO : utiliser le point d'interrogation et ?null pour initialiser certaines variables   => https://github.com/yiisoft/router/blob/master/src/Route.php#L19 pour fonctionner en version 7.4 !!!!
final class Route implements RequestHandlerInterface, ContainerAwareInterface, EventDispatcherAwareInterface
{
    use PipelineTrait, ContainerAwareTrait, EventDispatcherAwareTrait;

    public const ATTRIBUTE = '__Route__';

    /** @var string|null */
    private $host;
    /** @var string|null */
    private $scheme;
    /** @var int|null */
    private $port;
    /** @var array */
    private $requirements = [];
    /** @var array */
    private $defaults = [];
    /** @var string|null */
    private $name;
    /** @var string */
    private $path;

    /**
     * List of supported HTTP methods for this route (GET, POST etc.).
     *
     * @var array
     */
    // Créer une RouteInterface et ajouter ces verbs dans l'interface : https://github.com/spiral/router/blob/master/src/RouteInterface.php#L26
    // TODO : cette initialisation ne semble pas nécessaire !!!!
    private $methods = Method::ANY; //['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'];

    // TODO : extraire les default et requirements du pattern ??? https://github.com/symfony/routing/blob/5.x/Route.php#L536 (cf méthode extractInlineDefaultsAndRequirements())
    public function __construct(string $pattern)
    {
        $this->setPath($pattern);
    }

    public static function get(string $pattern): self
    {
        return self::create($pattern, [Method::GET]);
    }

    public static function post(string $pattern): self
    {
        return self::create($pattern, [Method::POST]);
    }

    public static function put(string $pattern): self
    {
        return self::create($pattern, [Method::PUT]);
    }

    public static function delete(string $pattern): self
    {
        return self::create($pattern, [Method::DELETE]);
    }

    public static function patch(string $pattern): self
    {
        return self::create($pattern, [Method::PATCH]);
    }

    public static function head(string $pattern): self
    {
        return self::create($pattern, [Method::HEAD]);
    }

    public static function options(string $pattern): self
    {
        return self::create($pattern, [Method::OPTIONS]);
    }

    public static function trace(string $pattern): self
    {
        return self::create($pattern, [Method::TRACE]);
    }

    public static function any(string $pattern): self
    {
        return self::create($pattern, Method::ANY);
    }

    private static function create(string $pattern, array $methods): self
    {
        $route = new static($pattern);
        $route->setAllowedMethods($methods);

        return $route;
    }

    /**
     * Specifes a handler that should be invoked for a matching route.
     *
     * @param mixed $handler
     *
     * @return Route
     */
    // TODO : gérer le cas ou l'utilisateur n'appel pas cette méthode et donc que le $this->handler est null, car on aura un typeerror quand on va récupérer la valeur via le getteur getHandler() qui doit retourner un objet de type ServerRequestHandlerInterface !!!!!!!!!!!!
    // TODO : ajouter le typehint pour la phpdoc.
    // TODO : attention ca va casser la commande RouteListCommand si on ne résoue pas le target en live, car dans ce cas le $this->handler ne sera pas initialisé !!!!
    public function to($target): self
    {
        $handler = $this->resolveHandler($target);

        // TODO : on devrait pas plutot déporter ce bout de code dans la classe UrlMatcher lorsqu'on inject les routes ? car je pense qu'il y a un risque que l'ajout de valeurs par default ou de requirements ne perturbent la génération du lien pour l'url lorsqu'on utilisera la classe UrlGenerator.
        // TODO : eventuellement déplacer ce bout de code dans un listener sur l'événement RouteMatchedEvent !!!!!
        if ($handler instanceof TargetInterface) {
            $this->addDefaults($handler->getDefaults());
            $this->addRequirements($handler->getRequirements());
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * Return the defined handler for the route. The value could be null.
     *
     * @return null|RequestHandlerInterface
     */
    /*
    public function getHandler(): ?RequestHandlerInterface
    {
        return $this->handler;
    }*/

    /**
     * Add a middleware to the end of the stack.
     *
     * @param string|MiddlewareInterface or an array of such arguments $middlewares
     *
     * @return $this
     */
    // TODO : gérer aussi les tableaux de middleware, ainsi que les tableaux de tableaux de middlewares
    // TODO : il faudrait pas ajouter un mécanisme pour éviter les doublons lorsqu'on ajoute un middleware ???? en vérifiant le get_class par exemple.
    public function middleware($middleware): self
    {
        $this->middlewares[] = $this->resolveMiddleware($middleware);

        return $this;
    }

    /**
     * Returns the pattern for the path.
     *
     * @return string The path pattern
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets the pattern for the path.
     *
     * @return $this
     */
    public function setPath(string $pattern): self
    {
        // A pattern must start with a slash and must not have multiple slashes at the beginning because the
        // generated path for this route would be confused with a network path, e.g. '//domain.com/path'.
        $this->path = '/' . ltrim(trim($pattern), '/');

        return $this;
    }

    /**
     * Returns the defaults.
     *
     * @return array The defaults
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Sets the defaults.
     *
     * @param array $defaults The defaults
     *
     * @return $this
     */
    public function setDefaults(array $defaults): self
    {
        $this->defaults = [];

        return $this->addDefaults($defaults);
    }

    /**
     * Adds defaults.
     *
     * @param array $defaults The defaults
     *
     * @return $this
     */
    public function addDefaults(array $defaults): self
    {
        // TODO : faire un assert que $name est bien une string sinon lever une exception !!!!
        foreach ($defaults as $name => $default) {
            $this->defaults[$name] = $default;
        }

        return $this;
    }

    /**
     * Gets a default value.
     *
     * @param string $name A variable name
     *
     * @return mixed The default value or null when not given
     */
    public function getDefault(string $name)
    {
        return $this->defaults[$name] ?? null;
    }

    /**
     * Sets a default value.
     *
     * @param string $name    A variable name
     * @param mixed  $default The default value
     *
     * @return $this
     */
    public function setDefault(string $name, $default): self
    {
        $this->defaults[$name] = $default;

        return $this;
    }

    /**
     * Checks if a default value is set for the given variable.
     *
     * @param string $name A variable name
     *
     * @return bool true if the default value is set, false otherwise
     */
    public function hasDefault(string $name): bool
    {
        return array_key_exists($name, $this->defaults);
    }

    /**
     * Proxy method for "setDefault()".
     *
     * @param string $name    A variable name
     * @param mixed  $default The default value
     *
     * @return $this
     */
    public function value(string $variable, $default): self
    {
        return $this->setDefault($variable, $default);
    }

    /**
     * Returns the requirements.
     *
     * @return array The requirements
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Sets the requirements.
     *
     * @param array $requirements The requirements
     *
     * @return $this
     */
    public function setRequirements(array $requirements): self
    {
        $this->requirements = [];

        return $this->addRequirements($requirements);
    }

    /**
     * Adds requirements.
     *
     * @param array $requirements The requirements
     *
     * @return $this
     */
    public function addRequirements(array $requirements): self
    {
        // TODO : lever une exception si la key et le $regex ne sont pas des strings !!!!!
        /*
        if (! is_string($regex)) {
            throw new InvalidArgumentException(sprintf('Routing requirement for "%s" must be a string.', $key));
        }*/

        foreach ($requirements as $key => $regex) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        }

        return $this;
    }

    /**
     * Returns the requirement for the given key.
     *
     * @param string $key The key
     *
     * @return string|null The regex or null when not given
     */
    public function getRequirement(string $key): ?string
    {
        return $this->requirements[$key] ?? null;
    }

    /**
     * Checks if a requirement is set for the given key.
     *
     * @param string $key A variable name
     *
     * @return bool true if a requirement is specified, false otherwise
     */
    public function hasRequirement(string $key): bool
    {
        return array_key_exists($key, $this->requirements);
    }

    // TODO : avoir la possibilité de passer un tableau ? si on détecte que c'est un is_array dans le getargs() on appel la méthode addReqirements() pour un tableau, sinon on appel setRequirement()
    public function assert(string $key, string $regex): self
    {
        return $this->setRequirement($key, $regex);
    }

    /**
     * Sets a requirement for the given key.
     *
     * @param string $key   The key
     * @param string $regex The regex
     *
     * @return $this
     */
    public function setRequirement(string $key, string $regex): self
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);

        return $this;
    }

    // remove the char "^" at the start of the regex, and the final "$" char at the end of the regex
    // TODO : éviter les yoda comparaisons
    private function sanitizeRequirement(string $key, string $regex): string
    {
        if ($regex !== '' && $regex[0] === '^') {
            $regex = substr($regex, 1); // returns false for a single character
        }
        if (substr($regex, -1) === '$') {
            $regex = substr($regex, 0, -1);
        }
        if ($regex === '') {
            // TODO : lever une RouteException
            throw new InvalidArgumentException(sprintf('Routing requirement for "%s" cannot be empty.', $key));
        }

        return $regex;
    }

    /**
     * Get the route name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the route name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Proxy method for "setName()".
     *
     * @param string $name
     *
     * @return $this
     */
    public function name(string $name): self
    {
        return $this->setName($name);
    }

    /**
     * Get supported HTTP method(s).
     *
     * @return array
     */
    // TODO : renommer cette méthode en getMethods()
    public function getAllowedMethods(): array
    {
        return array_unique($this->methods);
    }

    /**
     * Set supported HTTP method(s).
     *
     * @param array
     *
     * @return self
     */
    // TODO : filtrer les méthodes cf exemple :       https://github.com/slimphp/Slim-Psr7/blob/master/src/Request.php#L155
    // TODO : renommer cette méthode en setMethods()
    // TODO : ajouter les throws dans la phpdoc !!!
    public function setAllowedMethods(array $methods): self
    {
        // TODO : reporter ce test&throw dans la méthode Method::validateHttpMethods() !!!!
        if ($methods === []) {
            // TODO : lever une RouteException
            throw new InvalidArgumentException(
                'HTTP methods argument was empty; must contain at least one method'
            );
        }

        $this->methods = Method::validateHttpMethods($methods); // TODO : faire un try/catch et convertir l'exception en une RouteException !!!!

        return $this;
    }

    /*
    public function method(string $method, string ...$methods): self
    {
        array_unshift($methods, $method);

        return $this->setAllowedMethods($methods);
    }*/

    /**
     * Proxy method for "setAllowedMethods()".
     *
     * @param string|array ...$methods
     */
    // TODO : vérifier si en php8 le variadic est plus simple d'utilisation => https://github.com/illuminate/routing/blob/master/Route.php#L868
    public function method(...$methods): self
    {
        //$methods = is_array($methods[0]) ? $methods[0] : $methods;

        // TODO : on pourrait pas simplifier le if en utilisant un exemple comme ca :     $methods = $methods && is_array($methods[0]) ? $methods[0] : $methods;

        // Allow passing arrays of methods or individual lists of methods
        if (isset($methods[0]) && is_array($methods[0]) && count($methods) === 1) {
            //$methods = array_shift($methods);
            $methods = $methods[0];
        }

        return $this->setAllowedMethods($methods);
    }

    /**
     * Get the host condition.
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Set the host condition.
     *
     * @param string $host
     *
     * @return static
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Proxy method for "setHost()".
     *
     * @param string $host
     *
     * @return static
     */
    public function host(string $host): self
    {
        return $this->setHost($host);
    }

    /**
     * Get the scheme condition.
     *
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * Set the scheme condition.
     *
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme(string $scheme): self
    {
        $this->scheme = strtolower($scheme);

        return $this;
    }

    /**
     * Proxy method for "setScheme()".
     *
     * @param string $scheme
     *
     * @return static
     */
    public function scheme(string $scheme): self
    {
        return $this->setScheme($scheme);
    }

    /**
     * Helper - Sets the scheme requirement to HTTP (no HTTPS).
     *
     * @return static
     */
    public function requireHttp(): self
    {
        return $this->setScheme('http');
    }

    /**
     * Helper - Sets the scheme requirement to HTTPS.
     *
     * @return static
     */
    public function requireHttps(): self
    {
        return $this->setScheme('https');
    }

    /**
     * Get the port condition.
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * Set the port condition.
     *
     * @param int $port
     *
     * @return static
     */
    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Proxy method for "setPort()".
     *
     * @param int $port
     *
     * @return static
     */
    public function port(int $port): self
    {
        return $this->setPort($port);
    }

    /**
     * Execute the route using a pipeline (send request throw middlewares and final handler).
     *
     * Add the the route attribute to the request in case user need it in final handler.
     * And bind a fresh request in the container.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    // TODO : lever un RouteMatchedEvent :
    //https://github.com/laravel/framework/blob/8.x/src/Illuminate/Routing/Events/RouteMatched.php
    //https://github.com/laravel/framework/blob/574aaece57561e4258d5f9ab4275009d4355180a/src/Illuminate/Routing/Router.php#L669
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO : virer cette vérifications supperflue ????
        // This case shoudn't really happen because the container is injectected via 'Map::addRoute()'.
        if (! $this->hasContainer()) {
            throw new RouteException('Unable to configure route pipeline without associated container.'); // TODO : utiliser un RoutingException !!!!
        }

        $this->getEventDispatcher()->dispatch(new RouteMatchedEvent($this, $request));

        // TODO : stocker plutot les defaults dans un attribut de la request 'pass' comme fait cakephp : https://github.com/cakephp/cakephp/blob/4981fcd4de9941174a9e3f4430278f71d2eb81b9/src/Routing/Route/Route.php#L486
        // Store the Route default attribute values in the Request attributes (only if not already presents).
        foreach ($this->defaults as $parameter => $value) {
            if ($request->getAttribute($parameter) === null) {
                $request = $request->withAttribute($parameter, $value);
            }
        }

        // TODO : il faudrait plutot créer un CurrentRoute::class et l'attacher dans la request !!! voir même le binder dans le container !!!!
        // TODO : attacher une current route dans la request ??? https://github.com/yiisoft/router/blob/4a762f14c9e338e94fc27dd3768b45712409ae4a/src/Middleware/Router.php#L59
        // TODO : modifier le RoutingServiceProvider pour récupérer la classe CurrentRoute::class via la request stockée dans le container !!!
        // Store the current Route instance in the attributes (used during 'Injector' parameters resolution).
        $request = $request->withAttribute(static::ATTRIBUTE, $this);

        return $this->getPipeline()->handle($request);
    }
}

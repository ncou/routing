<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Chiron\Container\Container;
use Chiron\Container\ContainerAwareInterface;
use Chiron\Container\ContainerAwareTrait;
use Chiron\Http\Message\RequestMethod as Method;
use Chiron\Pipeline\PipelineTrait;
use Chiron\Routing\Exception\RouteException;
use Chiron\Routing\Target\TargetInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

//https://github.com/symfony/routing/blob/master/Route.php

// Ajouter les middleware comme des options :    https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteConfigureTrait.php#L272
// ajouter des extra dans la route :  https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteConfigureTrait.php#L353
// et l'utilitaire pour faire un deep merge (mergeRecursive) :   https://github.com/ventoviro/windwalker-utilities/blob/master/Arr.php#L803
// et le bout de code pour récupérer les extra : https://github.com/ventoviro/windwalker/blob/8b1aba30967dd0e6c4374aec0085783c3d0f88b4/src/Router/Route.php#L515

// TODO : remplacer le terme Alias dans les commentaires par Proxy
// TODO : passer la classe en final et virer les champs protected
// TODO : utiliser le point d'interrogation et ?null pour initialiser certaines variables   => https://github.com/yiisoft/router/blob/master/src/Route.php#L19 pour fonctionner en version 7.4 !!!!
class Route implements RequestHandlerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use PipelineTrait;

    public const ATTRIBUTE = '__Route__';

    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var string|null
     */
    protected $scheme;

    /**
     * @var int|null
     */
    protected $port;

    /** @var array */
    private $requirements = [];

    /** @var array */
    private $defaults = [];

    /** @var string|null */
    private $name;

    /**
     * The route path pattern (The URL pattern (e.g. "article/[:year]/[i:category]")).
     *
     * @var string
     */
    private $path;

    private $target;

    /**
     * List of supported HTTP methods for this route (GET, POST etc.).
     *
     * @var array
     */
    // Créer une RouteInterface et ajouter ces verbs dans l'interface : https://github.com/spiral/router/blob/master/src/RouteInterface.php#L26
    // TODO : cette initialisation ne semble pas nécessaire !!!!
    private $methods = Method::ANY; //['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'];

    // TODO : extraire les default et requirements du pattern ??? https://github.com/symfony/routing/blob/5.x/Route.php#L536 (cf méthode extractInlineDefaultsAndRequirements())
    // TODO : créer une méthode public setPath() qui serait appellée dans le constructeur ??? cela permet de modifier plus tard le path je suppose !!!!
    public function __construct(string $pattern)
    {
        // A pattern must start with a slash and must not have multiple slashes at the beginning because the
        // generated path for this route would be confused with a network path, e.g. '//domain.com/path'.
        $this->path = '/' . ltrim(trim($pattern), '/');
    }

    public static function get(string $pattern): self
    {
        return self::map($pattern, [Method::GET]);
    }

    public static function post(string $pattern): self
    {
        return self::map($pattern, [Method::POST]);
    }

    public static function put(string $pattern): self
    {
        return self::map($pattern, [Method::PUT]);
    }

    public static function delete(string $pattern): self
    {
        return self::map($pattern, [Method::DELETE]);
    }

    public static function patch(string $pattern): self
    {
        return self::map($pattern, [Method::PATCH]);
    }

    public static function head(string $pattern): self
    {
        return self::map($pattern, [Method::HEAD]);
    }

    public static function options(string $pattern): self
    {
        return self::map($pattern, [Method::OPTIONS]);
    }

    public static function trace(string $pattern): self
    {
        return self::map($pattern, [Method::TRACE]);
    }

    public static function any(string $pattern): self
    {
        return self::map($pattern, Method::ANY);
    }

    // TODO : harmoniser la signature de la méthode avec la classe RouteCollection qui contient aussi une méthode "map()".
    public static function map(string $pattern, array $methods): self
    {
        $route = new static($pattern);
        $route->setAllowedMethods($methods);

        return $route;
    }

    /**
     * Speicifes a handler that should be invoked for a matching route.
     *
     * @param RequestHandlerInterface $handler the handler could also be a TargetInterface (it implements the RequestHandlerInterface)
     *
     * @return Route
     */
    // TODO : gérer le cas ou l'utilisateur n'appel pas cette méthode et donc que le $this->handler est null, car on aura un typeerror quand on va récupérer la valeur via le getteur getHandler() qui doit retourner un objet de type ServerRequestHandlerInterface !!!!!!!!!!!!
    public function to($target): self
    {
        $this->target = $target;

        // Resolve the handler if the container is presents, else the resolution will be done later.
        if ($this->hasContainer()) {
            $this->prepareHandler($target);
        }

        return $this;
    }

    private function prepareHandler($target): void
    {
        $handler = $this->resolveHandler($target);

        // TODO : on devrait pas plutot déporter ce bout de code dans la classe UrlMatcher lorsqu'on inject les routes ? car je pense qu'il y a un risque que l'ajout de valeurs par default ou de requirements ne perturbent la génération du lien pour l'url lorsqu'on utilisera la classe UrlGenerator.
        if ($handler instanceof TargetInterface) {
            $this->addDefaults($handler->getDefaults());
            $this->addRequirements($handler->getRequirements());
        }

        $this->handler = $handler;
    }

    public function getPath(): string
    {
        return $this->path;
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
     * Alias for setDefault.
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
     * Alia function for "setName()".
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
    // filtrer les méthodes cf exemple :       https://github.com/slimphp/Slim-Psr7/blob/master/src/Request.php#L155
    public function setAllowedMethods(array $methods): self
    {
        if (empty($methods)) {
            throw new InvalidArgumentException(
                'HTTP methods argument was empty; must contain at least one method'
            );
        }

        $this->methods = Method::validateHttpMethods($methods);

        return $this;
    }

    /*
    public function method(string $method, string ...$methods): self
    {
        array_unshift($methods, $method);

        return $this->setAllowedMethods($methods);
    }*/

    /**
     * Alia function for "setAllowedMethods()".
     *
     * @param string|array ...$middleware
     */
    // TODO : faire plutot des méthodes : getMethods() et setMethods()
    // TODO : à renommer en allows() ????
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
     * Alias function for "setHost()".
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
     * Alias function for "setScheme()".
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
     * Alias function for "setPort()".
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
     * Add a middleware to the end of the stack.
     *
     * @param string|MiddlewareInterface or an array of such arguments $middlewares
     *
     * @return $this
     */
    // TODO : gérer aussi les tableaux de middleware, ainsi que les tableaux de tableaux de middlewares
    public function middleware($middleware): self
    {
        // Resolve the middleware if the container is presents, else the resolution will be done later.
        if ($this->hasContainer()) {
            $middleware = $this->resolveMiddleware($middleware);
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Extend the setContainer() function defined in the ContianerAwareTrait.
     * We resolve the middleware stack and the handler the values are corrects.
     * This verification is done here to allow throwing exceptions during the bootloading !
     *
     * @param Container $container
     *
     * @return $this
     */
    // TODO : faire une sorte de extend du ContainerAwareTrait !!!! plutot que de faire cette redéfinition de la méthode ci dessous !!!
    public function setContainer(Container $container): ContainerAwareInterface
    {
        $this->container = $container;

        // Resolve the middlewares already added, exception is thrown on resolution fail.
        foreach ($this->middlewares as &$middleware) {
            $middleware = $this->resolveMiddleware($middleware);
        }

        // Resolve the handler only if already set, exception is thrown on resolution fail.
        if ($this->target !== null) {
            $this->prepareHandler($this->target);
        }

        return $this;
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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // This case shoudn't really happen because the container is injectected via 'RouteCollection::addRoute()'.
        if (! $this->hasContainer()) {
            throw new RouteException('Unable to configure route pipeline without associated container.');
        }

        // Store the Route default attribute values in the Request attributes (only if not already presents).
        foreach ($this->defaults as $parameter => $value) {
            if ($request->getAttribute($parameter) === null) {
                $request = $request->withAttribute($parameter, $value);
            }
        }

        // Store the current Route instance in the attributes (used during 'Injector' parameters resolution).
        $request = $request->withAttribute(self::ATTRIBUTE, $this);

        return $this->getPipeline()->handle($request);
    }
}

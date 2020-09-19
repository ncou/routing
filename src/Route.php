<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Chiron\Routing\Target\TargetInterface;
use Chiron\Routing\Traits\MiddlewareAwareInterface;
use Chiron\Routing\Traits\MiddlewareAwareTrait;
use Chiron\Routing\Traits\RouteConditionHandlerInterface;
use Chiron\Routing\Traits\RouteConditionHandlerTrait;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;

//https://github.com/symfony/routing/blob/master/Route.php

// Ajouter les middleware comme des options :    https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteConfigureTrait.php#L272
// ajouter des extra dans la route :  https://github.com/ventoviro/windwalker-core/blob/aaf68793043e84c1374bda8065eebdbc347862ac/src/Core/Router/RouteConfigureTrait.php#L353
// et l'utilitaire pour faire un deep merge (mergeRecursive) :   https://github.com/ventoviro/windwalker-utilities/blob/master/Arr.php#L803
// et le bout de code pour récupérer les extra : https://github.com/ventoviro/windwalker/blob/8b1aba30967dd0e6c4374aec0085783c3d0f88b4/src/Router/Route.php#L515

// TODO : remplacer le terme Alias dans les commentaires par Proxy
// TODO : passer la classe en final et virer les champs protected
// TODO : utiliser le point d'interrogation et ?null pour initialiser certaines variables   => https://github.com/yiisoft/router/blob/master/src/Route.php#L19 pour fonctionner en version 7.4 !!!!
class Route implements MiddlewareAwareInterface
{
    // TODO : virer la classe MiddlewareAwareTrait et lister les méthodes middleware() / getStackMiddleware() dans l'interface.
    use MiddlewareAwareTrait;

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

    /**
     * Handler assigned to be executed when route is matched.
     *
     * @var RequestHandlerInterface
     */
    private $handler;

    /**
     * List of supported HTTP methods for this route (GET, POST etc.).
     *
     * @var array
     */
    // Créer une RouteInterface et ajouter ces verbs dans l'interface : https://github.com/spiral/router/blob/master/src/RouteInterface.php#L26
    // TODO : cette initialisation ne semble pas nécessaire !!!!
    // TODO : utiliser directement la constante Method::ANY
    private $methods = Method::ANY; //['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'];


    public function __construct(string $path)
    {
        // A path must start with a slash and must not have multiple slashes at the beginning because it would be confused with a network path, e.g. '//domain.com/path'.
        //$this->path = sprintf('/%s', ltrim($path, '/'));
        $this->path = '/' . ltrim($path, '/');
    }

    public static function get(string $path): self
    {
        return self::map($path, [Method::GET]);
    }
    public static function post(string $path): self
    {
        return self::map($path, [Method::POST]);
    }
    public static function put(string $path): self
    {
        return self::map($path, [Method::PUT]);
    }
    public static function delete(string $path): self
    {
        return self::map($path, [Method::DELETE]);
    }
    public static function patch(string $path): self
    {
        return self::map($path, [Method::PATCH]);
    }
    public static function head(string $path): self
    {
        return self::map($path, [Method::HEAD]);
    }
    public static function options(string $path): self
    {
        return self::map($path, [Method::OPTIONS]);
    }
    public static function trace(string $path): self
    {
        return self::map($path, [Method::TRACE]);
    }
    public static function any(string $path): self
    {
        return self::map($path, Method::ANY);
    }
    public static function map(string $path, array $methods): self
    {
        $route = new static($path);
        $route->setAllowedMethods($methods);

        return $route;
    }

    /**
     * Speicifes a handler that should be invoked for a matching route.
     *
     * @param RequestHandlerInterface $handler the handler could also be a TargetInterface (it implements the RequestHandlerInterface)
     * @return Route
     */
    // TODO : gérer le cas ou l'utilisateur n'appel pas cette méthode et donc que le $this->handler est null, car on aura un typeerror quand on va récupérer la valeur via le getteur getHandler() qui doit retourner un objet de type ServerRequestHandlerInterface !!!!!!!!!!!!
    public function to(RequestHandlerInterface $handler): self
    {
        // TODO : on devrait pas plutot déporter ce bout de code dans la classe UrlMatcher lorsqu'on inject les routes ? car je pense qu'il y a un risque que l'ajout de valeurs par default ou de requirements ne perturbent la génération du lien pour l'url lorsqu'on utilisera la classe UrlGenerator.
        if ($handler instanceof TargetInterface) {
        //if (is_subclass_of($handler, TargetInterface::class)) {
            $this->addDefaults($handler->getDefaults());
            $this->addRequirements($handler->getRequirements());
        }

        $this->handler = $handler;

        return $this;
    }


    // return : null|RequestHandlerInterface The return null arrive only if the ->to() function hasn't been called (in case the user want to use the last middleware as a response creator)
    public function getHandler(): ?RequestHandlerInterface
    {
        return $this->handler;
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
        if ('' !== $regex && '^' === $regex[0]) {
            $regex = substr($regex, 1); // returns false for a single character
        }
        if ('$' === substr($regex, -1)) {
            $regex = substr($regex, 0, -1);
        }
        if ('' === $regex) {
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
        if (isset($methods[0])
            && is_array($methods[0])
            && count($methods) === 1
        ) {
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
     * @param string $scheme
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
     * @param string $scheme
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
}

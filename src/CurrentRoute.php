<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Chiron\Http\Message\RequestMethod as Method;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LogicException;
use Psr\Http\Message\UriInterface;

// TODO : créer une facade pour accéder à cette classe ??? cela permettrai de récupérer les attributs de la route ou l'uri de la route directement dans une vue ou via un twig helper par exemple.

// TODO : harmoniser le nom des méthodes, on parle de getMethods() alors que dans la route c'est getAllowedMethods(), idem pour getPattern() versus getPath() nommée dans la Route::class

final class CurrentRoute
{
    public const ATTRIBUTE = '__CurrentRoute__';

    /**
     * Current Route.
     */
    private Route $route;

    /**
     * Current URI.
     */
    private UriInterface $uri;

    /**
     * Current Route arguments.
     *
     * @var string[]
     *
     * @psalm-var array<string, string>
     */
    private array $arguments;


    /**
     * @param array<string, string> $arguments
     */
    public function __construct(UriInterface $uri, Route $route, array $arguments)
    {
        $this->route = $route;
        $this->uri = $uri;
        $this->arguments = $arguments;
    }

    /**
     * Returns the current route name.
     *
     * @return string|null The current route name.
     */
    public function getName(): ?string
    {
        return $this->route->getName();
    }

    /**
     * Returns the current route host.
     *
     * @return string|null The current route host.
     */
    public function getHost(): ?string
    {
        return $this->route->getHost();
    }

    /**
     * Returns the current route scheme.
     *
     * @return string|null The current route scheme.
     */
    public function getScheme(): ?string
    {
        return $this->route->getScheme();
    }

    /**
     * Returns the current route port.
     *
     * @return int|null The current route port.
     */
    public function getPort(): ?int
    {
        return $this->route->getPort();
    }

    /**
     * Returns the current route pattern.
     *
     * @return string The current route pattern.
     */
    public function getPattern(): string
    {
        return $this->route->getPath();
    }

    /**
     * Returns the current route methods.
     *
     * @return string[] The current route methods.
     */
    public function getMethods(): array
    {
        return $this->route->getAllowedMethods();
    }

    /**
     * Returns the current URI.
     *
     * @return UriInterface The current URI.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @return string[] Arguments.
     * @psalm-return array<string, string>
     */
    // TODO : charger le nom de cette méthode en getValues() ??? au lieu de garder le terme "argument" ??? sachant qu'on va surement merger les valeurs par défault de la route.
    public function getArguments(): array
    {
        // TODO : il faudrait suremement merger le tableau retourné avec les valeurs du $route->getDefaults()
        return $this->arguments;
    }

    // TODO : charger le nom de cette méthode en getValue() ??? au lieu de garder le terme "argument" ??? sachant qu'on va surement merger les valeurs par défault de la route (lors qu'on recherche une valeur à partir d'un $name).
    public function getArgument(string $name, ?string $default = null): ?string
    {
        // TODO : il faudrait suremement tester si l'argument existe dans le tableau $this->arguments[], puis dans le tableau $route->getDefaults(), et si ce n'est pas le cas retourner la variable $default. => Si on a mergé les défaults dans la méthode getArguments() alors il faudra utiliser ce tableau de résultat pour faire la recherche si la valeur existe, et si ce n'est pas le cas retourner la valeur par défault !!!!
        return $this->arguments[$name] ?? $default;
    }
}

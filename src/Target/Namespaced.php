<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Injector\Injector;

/**
 * Provides ability to invoke any controller from given namespace.
 *
 * ```php
 * new Namespaced("App\Controllers");
 * ```
 */
final class Namespaced implements RequestHandlerInterface
{
    /** @var ContainerInterface */
    private $container;
    /** @var string */
    private $namespace;
    /** @var string */
    private $postfix;

    /**
     * @param ContainerInterface $container
     * @param string $namespace
     * @param string $postfix
     */
    public function __construct(ContainerInterface $container, string $namespace, string $postfix = 'Controller')
    {
        $this->container = $container;
        $this->namespace = rtrim($namespace, '\\');
        $this->postfix = ucfirst($postfix);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controllerName = $request->getAttribute('controller');

        if ($controllerName === null) {
            throw new \RuntimeException('Request does not contain controller attribute.');
        }

        $class = sprintf(
            '%s\\%s%s',
            $this->namespace,
            $this->classify($controllerName),
            $this->postfix
        );

        //$controller = $this->container->get($class);

        $action = $request->getAttribute('action');
        if ($action === null) {
            throw new \RuntimeException('Request does not contain action attribute.');
        }

/*
        if (!method_exists($controller, $action)) {
            // TODO : utiliser une exception HTTP ici ???
            throw new \RuntimeException('Bad Request.');
            //return $handler->handle($request);
        }*/

        return (new Injector($this->container))->call([$class, $action], [$request]);
    }

    public function getDefaults(): array
    {
        return ['controller' => null, 'action' => null];
    }

    public function getRequirements(): array
    {
        return ['controller' => null, 'action' => null];
    }

    /**
     * Converts a word into the format for a Doctrine class name. Converts 'table_name' to 'TableName'.
     */
    private function classify(string $word) : string
    {
        return str_replace([' ', '_', '-'], '', ucwords($word, ' _-'));
    }
}

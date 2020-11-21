<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Chiron\Container\Container;
use Chiron\Http\CallableHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides ability to invoke any controller from given namespace.
 *
 * ```php
 * new Namespaced("App\Controllers");
 * ```
 */
final class Namespaced extends CallableHandler implements TargetInterface
{
    /** @var string */
    private $namespace;
    /** @var string */
    private $postfix;

    /**
     * @param Container $container
     * @param string    $namespace
     * @param string    $postfix
     */
    public function __construct(string $namespace, string $postfix = 'Controller')
    {
        $this->namespace = rtrim($namespace, '\\');
        $this->postfix = ucfirst($postfix);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controllerName = $request->getAttribute('controller');

        if ($controllerName === null) {
            // TODO : utiliser une classe spécifique style HandlerException ou TargetException ????
            throw new \RuntimeException('Request does not contain controller attribute.');
        }

        $class = sprintf(
            '%s\\%s%s',
            $this->namespace,
            $this->classify($controllerName),
            $this->postfix
        );

        $action = $request->getAttribute('action');
        if ($action === null) {
            // TODO : utiliser une classe spécifique style HandlerException ou TargetException ????
            throw new \RuntimeException('Request does not contain action attribute.');
        }

        $this->callable = [$class, $action];

        return parent::handle($request);
    }

    /**
     * Converts a word into the format for a Doctrine class name. Converts 'table_name' to 'TableName'.
     */
    private function classify(string $word): string
    {
        return str_replace([' ', '_', '-'], '', ucwords($word, ' _-'));
    }

    public function getDefaults(): array
    {
        return ['controller' => null, 'action' => null];
    }

    public function getRequirements(): array
    {
        return [];
    }
}

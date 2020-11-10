<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Chiron\Pipeline\CallableHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides ability to invoke from a given controller set:
 *
 * ```php
 * new Group(['signup' => SignUpController::class]);
 * ```
 */
final class Group extends CallableHandler implements TargetInterface
{
    /** @var array */
    private $controllers;

    /**
     * @param array $controllers
     */
    public function __construct(array $controllers)
    {
        $this->controllers = $controllers;
    }

    //return join('|', $values);
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controllerName = $request->getAttribute('controller');
        if ($controllerName === null) {
            // TODO : utiliser une classe spécifique style HandlerException ou TargetException ????
            throw new \RuntimeException('Request does not contain controller attribute.');
        }

        // TODO : on devrait pas vérifier via un isset que l'élément du tableau $this->controllers[xxx] existe bien ????
        $controller = $this->controllers[$controllerName];

        $action = $request->getAttribute('action');
        if ($action === null) {
            // TODO : utiliser une classe spécifique style HandlerException ou TargetException ????
            throw new \RuntimeException('Request does not contain action attribute.');
        }

        $this->callable = [$controller, $action];

        return parent::handle($request);
    }

    public function getDefaults(): array
    {
        return ['controller' => null, 'action' => null];
    }

    public function getRequirements(): array
    {
        return ['controller' => join('|', array_keys($this->controllers))];
    }
}

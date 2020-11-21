<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Chiron\Http\CallableHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Targets to all actions in specific controller. Variation of Action without action constrain.
 *
 * ```php
 * new Controller(HomeController::class);
 * ```
 */
final class Controller extends CallableHandler implements TargetInterface
{
    /** @var string */
    private $controller;

    /**
     * @param string $controller
     */
    public function __construct(string $controller)
    {
        $this->controller = $controller;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO : utiliser une classe spécifique style HandlerException ou TargetException ????
        $action = $request->getAttribute('action');
        if ($action === null) {
            throw new \RuntimeException('Request does not contain action attribute.');
        }

        $this->callable = [$this->controller, $action];

        return parent::handle($request);
    }

    public function getDefaults(): array
    {
        return ['action' => null];
    }

    public function getRequirements(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Chiron\Http\CallableHandler;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// TODO : mieux gérer les exceptions dans le cas ou il y a une erreur lors du $injector->call()
//exemple :   https://github.com/spiral/framework/blob/e63b9218501ce882e661acac284b7167b79da30a/src/Hmvc/src/AbstractCore.php#L67
//+         https://github.com/spiral/framework/blob/master/src/Router/src/CoreHandler.php#L199


/**
 * Targets to specific controller action or actions.
 *
 * ```php
 * new Action(HomeController::class, "index");
 * new Action(SingUpController::class, ["login", "logout"]); // creates <action> constrain
 * ```
 */
//https://github.com/PHP-DI/Slim-Bridge/blob/master/src/ControllerInvoker.php#L43
final class Action extends CallableHandler implements TargetInterface
{
    /** @var string */
    private $controller;
    /** @var array|string */
    private $action;

    /**
     * @param string       $controller Controller class name.
     * @param string|array $action     One or multiple allowed actions.
     */
    // TODO : initialiser le paramétre $astion avec la valeur par défaut 'index' ????
    public function __construct(string $controller, $action)
    {
        // TODO : if à virer car on est en php8 donc on peut forcer le paramétre à avoir un typehint égal à "string|array"
        if (! is_string($action) && ! is_array($action)) {
            // TODO : utiliser une classe spécifique style HandlerException ou TargetException ????
            throw new InvalidArgumentException(sprintf(
                'Action parameter must have type string or array, `%s` given.',
                gettype($action)
            ));
        }

        $this->controller = $controller;
        $this->action = $action;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $action = $request->getAttribute('action');
        $this->callable = [$this->controller, $action];

        return parent::handle($request);
    }

    public function getDefaults(): array
    {
        if (is_string($this->action)) {
            return ['action' => $this->action];
        } else {
            return ['action' => null];
        }
    }

    public function getRequirements(): array
    {
        if (is_string($this->action)) {
            return ['action' => $this->action];
        } else {
            return ['action' => join('|', $this->action)];
        }
    }
}

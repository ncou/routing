<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Container\ReflectionResolver;
use Chiron\Injector\Injector;
use Chiron\Injector\Exception\InvocationException;
use Chiron\Http\Exception\Client\BadRequestHttpException;
use InvalidArgumentException;

/**
 * Targets to specific controller action or actions.
 *
 * ```php
 * new Action(HomeController::class, "index");
 * new Action(SingUpController::class, ["login", "logout"]); // creates <action> constrain
 * ```
 *
 */
//https://github.com/PHP-DI/Slim-Bridge/blob/master/src/ControllerInvoker.php#L43
final class Action implements TargetInterface
{
    /** @var ContainerInterface */
    private $container;
    /** @var string */
    private $controller;
    /** @var array|string */
    private $action;
    /** @var Injector */
    private $injector;

    /**
     * @param ContainerInterface $container
     * @param string       $controller Controller class name.
     * @param string|array $action     One or multiple allowed actions.
     */
    // TODO : initialiser le paramétre $astion avec la valeur par défaut 'index' ????
    public function __construct(ContainerInterface $container, string $controller, $action)
    {
        if (!is_string($action) && !is_array($action)) {
            throw new InvalidArgumentException(sprintf(
                'Action parameter must have type string or array, `%s` given.',
                gettype($action)
            ));
        }

        $this->container = $container;
        $this->controller = $controller;
        $this->action = $action;

        $this->injector = new Injector($this->container);
    }

    //https://github.com/PHP-DI/Slim-Bridge/blob/master/src/ControllerInvoker.php#L43
    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        // TODO : lever une exception si le container>has() ne trouve pas le controller !!!!
        //$controller = $this->container->get($this->controller);

        //https://github.com/PHP-DI/Slim-Bridge/blob/master/src/ControllerInvoker.php#L43
        // TODO : à virer c'est pour un test !!!!
        $this->container->add(ServerRequestInterface::class, $request);


/*
        $default = null;
        if (is_string($this->action)) {
            $default = $this->action;
        }
        $action = $request->getAttribute('action', $default);
        */

        // TODO : lever une exception si action est null ????
        $action = $request->getAttribute('action');
        //$action = null;

        //$resolver = new ControllerResolver();

        //$resolved = $resolver->getController([$controller, $action], $request);


        //return (new ReflectionResolver($this->container))->call([$controller, $action], [$request]);
        //return (new Invoker($this->container))->call([$controller, $action], [$request]);


        try {
            $response = $this->injector->call([$this->controller, $action], [$request]);
        } catch (InvocationException $e) {
            // TODO : améliorer le code pour permettre de passer en paramétre l'exception précédente ($e) à cette http exception
            throw new BadRequestHttpException();
        }

        return $response;
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

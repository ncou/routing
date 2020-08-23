<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Container\ReflectionResolver;
use Chiron\Http\Exception\Client\BadRequestHttpException;
use InvalidArgumentException;

final class TargetFactory
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string       $controller Controller class name.
     * @param string|array $action     One or multiple allowed actions.
     */
    public function action(string $controller, $action): Action
    {
        return new Action($this->container, $controller, $action);
    }

    /**
     * @param callable|array|string       $callback
     */
    public function callback($callback): Callback
    {
        return new Callback($this->container, $callback);
    }

    /**
     * @param string       $controller
     */
    public function controller(string $controller): Controller
    {
        return new Controller($this->container, $controller);
    }

    /**
     * @param array       $controllers
     */
    public function group(array $controllers): Group
    {
        return new Group($this->container, $controllers);
    }

    /**
     * @param string       $namespace
     * @param string       $postfix
     */
    public function namespaced(string $namespace, string $postfix = 'Controller'): Namespaced
    {
        return new Namespaced($this->container, $namespace, $postfix);
    }
}

<?php

declare(strict_types=1);

namespace Chiron\Routing\Target;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Injector\Injector;

// TODO : améliorer le code avec cet exemple : https://github.com/spiral/framework/blob/master/src/Router/src/CoreHandler.php#L104
//https://github.com/middlewares/utils/blob/master/src/CallableHandler.php#L59

// TODO : mieux gérer les exceptions dans le cas ou il y a une erreur lors du $injector->call()    exemple :   https://github.com/spiral/framework/blob/e63b9218501ce882e661acac284b7167b79da30a/src/Hmvc/src/AbstractCore.php#L67       +         https://github.com/spiral/framework/blob/master/src/Router/src/CoreHandler.php#L199

/**
 * Callback wraps arbitrary PHP callback into object matching [[MiddlewareInterface]].
 * Usage example:
 *
 * ```php
 * $middleware = new CallbackMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
 *     if ($request->getParams() === []) {
 *         return new Response();
 *     }
 *     return $handler->handle($request);
 * });
 * $response = $middleware->process(Yii::$app->getRequest(), $handler);
 * ```
 *
 * @see MiddlewareInterface
 */
final class Callback implements TargetInterface
{
    private $container;
    /**
     * @var callable|array|string a PHP callback matching signature of [RequestHandlerInterface->handle(ServerRequestInterface $request)]]. // TODO : non c'est faux ce n'est pas obligatoirement une signature de type requesthandler !!!!
     */
    private $callback;

    public function __construct(ContainerInterface $container, $callback)
    {
        $this->container = $container;
        $this->callback = $callback;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO : à virer c'est pour un test !!!!
        $this->container->bind(ServerRequestInterface::class, $request);

        // TODO : il faut surement stocker la $request dans un tableau avec la clé = au nom de la classe pour permettre au Invoker de matcher la request avec via un Autowire avec ce paramétre du tableau
        //return (new Invoker($this->container))->call($this->callback, [$request]);
        //return (new Invoker($this->container))->call($this->callback, [ServerRequestInterface::class => $request]);
        return (new Injector($this->container))->call($this->callback, $request->getAttributes());
    }

    public function getDefaults(): array
    {
        return [];
    }

    public function getRequirements(): array
    {
        return [];
    }
}

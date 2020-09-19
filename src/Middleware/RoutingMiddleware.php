<?php

declare(strict_types=1);

namespace Chiron\Routing\Middleware;

// TODO : example : https://github.com/zendframework/zend-expressive-router/blob/master/src/Middleware/RouteMiddleware.php
// TODO : regarder ici https://github.com/zrecore/Spark/blob/master/src/Handler/RouteHandler.php    et https://github.com/equip/framework/blob/master/src/Handler/DispatchHandler.php

//namespace Middlewares;

use Chiron\Http\Exception\Client\MethodNotAllowedHttpException;
use Chiron\Http\Exception\Client\NotFoundHttpException;
//use Chiron\Http\Psr\Response;
use Chiron\Routing\Route;
use Chiron\Routing\MatchingResult;
use Chiron\Routing\UrlMatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingMiddleware implements MiddlewareInterface
{
    /** @var UrlMatcherInterface */
    private $matcher;

    public function __construct(UrlMatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     *
     * @throws NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->performRouting($request);

        return $handler->handle($request);
    }

    /**
     * Perform routing (use 'public' visibility to be called by the RoutingHandler if needed)
     *
     * @param  ServerRequestInterface $request PSR7 Server Request
     * @return ServerRequestInterface
     *
     * @throws NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function performRouting(ServerRequestInterface $request) : ServerRequestInterface
    {
        // TODO : il faudrait peut etre récupérer la réponse via un $handle->handle() pour récupérer les headers de la réponse + le charset et version 1.1/1.0 pour le passer dans les exceptions (notfound+methodnotallowed) car on va recréer une nouvelle response !!!! donc si ca se trouve les headers custom genre X-Powered ou CORS vont être perdus lorsqu'on va afficher les messages custom pour l'exception 404 par exemple !!!!

        $result = $this->matcher->match($request);

        // Http 405 error => Invalid Method
        if ($result->isMethodFailure()) {
            throw new MethodNotAllowedHttpException($result->getAllowedMethods());
        }
        // Http 404 error => Not Found
        if ($result->isFailure()) {
            throw new NotFoundHttpException();
        }

        // add some usefull information about the url used for the routing
        // TODO : faire plutot porter ces informations (method et uri utilisé) directement dans l'objet MatchingResult ??????
        //$request = $request->withAttribute('routeInfo', [$request->getMethod(), (string) $request->getUri()]);  // ou alors ne stocker que le path ??? cad utiliser $request->getUri()->getPath()

        // Store the actual route result in the request attributes, to be used/executed by the routing handler.
        return $request->withAttribute(MatchingResult::ATTRIBUTE, $result);
    }
}

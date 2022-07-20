<?php

declare(strict_types=1);

namespace Chiron\Routing\Middleware;

use Chiron\Http\Exception\Client\MethodNotAllowedHttpException;
use Chiron\Http\Exception\Client\NotFoundHttpException;
use Chiron\Routing\MatchingResult;
use Chiron\Routing\UrlMatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Routing\CurrentRoute;

final class RoutingMiddleware implements MiddlewareInterface
{
    /** @var UrlMatcherInterface */
    private $matcher;

    /**
     * @param UrlMatcherInterface $matcher
     */
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
     * Perform routing (method use 'public' visibility to be called by the RouteHandler if needed).
     *
     * @param  ServerRequestInterface $request PSR7 Server Request
     *
     * @return ServerRequestInterface
     *
     * @throws NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface
    {
        // TODO : renommer $matching en $result
        $matching = $this->matcher->match($request);

        // Error - Http 405 Invalid Method.
        if ($matching->isMethodFailure()) {
            throw new MethodNotAllowedHttpException($matching->getAllowedMethods());
        }
        // Error - Http 404 Not Found.
        if ($matching->isFailure()) {
            throw new NotFoundHttpException();
        }

        // TODO : code temporaire qu'il faudra améliorer !!!! eventuellement le déplacer dans la classe RouteHandler ???
        $currentRoute = new CurrentRoute(
            $request->getUri(),
            $matching->getMatchedRoute(),
            $matching->getMatchedParameters()
        );
        $request = $request->withAttribute(CurrentRoute::ATTRIBUTE, $currentRoute);


        // TODO : attacher une current route dans la request ???
        // https://github.com/yiisoft/router/blob/4a762f14c9e338e94fc27dd3768b45712409ae4a/src/Middleware/Router.php#L59
        // https://github.com/yiisoft/router/blob/4a762f14c9e338e94fc27dd3768b45712409ae4a/src/CurrentRoute.php
        // TODO : modifier le RoutingServiceProvider pour récupérer la classe CurrentRoute::class via la request stockée dans le container !!!

        // TODO : au final ca ne sert à rien de stocker cette classe dans les attributs de la request, il faudrait que dans la classe RouteHandler on utilise l'attribut CurrentRoute::ATTRIBUTE pour détecter si le routing a déjà été fait !!!! + modifier la classe RoutingServiceProvider pour ne pas faire la récupération de la classe MatchingResult dans la request !!!!

        // Store the actual route matching result in the request attributes (needed in RouteHandler).
        return $request->withAttribute(MatchingResult::ATTRIBUTE, $matching);
    }
}

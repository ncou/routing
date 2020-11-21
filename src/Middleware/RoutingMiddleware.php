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

class RoutingMiddleware implements MiddlewareInterface
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
        $matching = $this->matcher->match($request);

        // Http 405 Error - Invalid Method
        if ($matching->isMethodFailure()) {
            throw new MethodNotAllowedHttpException($matching->getAllowedMethods());
        }
        // Http 404 Error - Not Found
        if ($matching->isFailure()) {
            throw new NotFoundHttpException();
        }

        // Store the actual route matching result in the request attributes (needed in RouteHandler).
        return $request->withAttribute(MatchingResult::ATTRIBUTE, $matching);
    }
}

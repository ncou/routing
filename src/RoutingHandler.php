<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Routing\Middleware\RoutingMiddleware;

class RoutingHandler implements RequestHandlerInterface
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router) {
        $this->router = $router;
    }

    /**
     * This request handler is instantiated automatically in Http::seedRequestHandler().
     * It is at the very tip of the middleware queue meaning it will be executed
     * last and it detects whether or not routing has been performed in the user
     * defined middleware stack. In the event that the user did not perform routing
     * it is done here.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If routing hasn't been done, then do it now so we can dispatch
        if ($request->getAttribute(MatchingResult::ATTRIBUTE) === null) {
            $routingMiddleware = new RoutingMiddleware($this->router);
            $request = $routingMiddleware->performRouting($request);
        }

        $result = $request->getAttribute(MatchingResult::ATTRIBUTE);

        return $result->handle($request);
    }

}

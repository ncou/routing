<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Routing\Middleware\RoutingMiddleware;

class RoutingHandler implements RequestHandlerInterface
{
    /** @var UrlMatcherInterface */
    private $matcher;

    public function __construct(UrlMatcherInterface $matcher)
    {
        $this->matcher = $matcher;
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
        if (! $this->isRoutingPerformed($request)) {
            $routingMiddleware = new RoutingMiddleware($this->matcher);
            $request = $routingMiddleware->performRouting($request);
        }

        $result = $request->getAttribute(MatchingResult::ATTRIBUTE);

        return $result->handle($request);
    }

    /**
     * If the attribute MatchingResult::ATTRIBUTE is not presents this mean the
     * routing middleware hasn't been called (not present in the middleware stack).
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isRoutingPerformed(ServerRequestInterface $request): bool
    {
        return $request->getAttribute(MatchingResult::ATTRIBUTE) !== null;
    }
}

<?php

declare(strict_types=1);

//https://github.com/thephpleague/route/blob/master/src/Middleware/MiddlewareAwareTrait.php

namespace Chiron\Routing\Traits;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareAwareInterface
{
    /**
     * Add a middleware to the stack.
     *
     * @param string|callable|MiddlewareInterface or an array of such arguments $middlewares
     * @param bool                                                              $addOnTop    Used to prepend a middleware, by default we add the middleware at the end of the stack
     *
     * @return static
     */
    //public function middleware($middleware): MiddlewareAwareInterface;
    public function middleware($middlewares, bool $addOnTop = false): MiddlewareAwareInterface;

    /**
     * Add multiple middleware to the stack.
     *
     * @param string[]|callable[]|MiddlewareInterface[] $middlewares
     *
     * @return static
     */
    //public function middlewares(array $middlewares): MiddlewareAwareInterface;

    /**
     * Prepend a middleware to the stack.
     *
     * @param string|callable|MiddlewareInterface $middleware
     *
     * @return static
     */
    //public function prependMiddleware($middleware): MiddlewareAwareInterface;

    /**
     * Shift a middleware from beginning of stack.
     *
     * @return \Psr\Http\Server\MiddlewareInterface|null
     */
    //public function shiftMiddleware() : MiddlewareInterface;

    /**
     * Get the stack of middleware.
     *
     * @return \Psr\Http\Server\MiddlewareInterface[]
     */
    public function getMiddlewareStack(): array;
}

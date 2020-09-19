<?php

declare(strict_types=1);

namespace Chiron\Routing;

use Psr\Http\Message\ServerRequestInterface;

interface UrlMatcherInterface
{

    /**
     * Match a request uri with a Route pattern.
     *
     * @param ServerRequestInterface $request
     *
     * @return MatchingResult
     *
     * @throws Exception\RouterException If an internal problem occured
     */
    // TODO : voir si on garde le tag @throw dans la phpdoc.
    public function match(ServerRequestInterface $request): MatchingResult;
}

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
     * @throws Exception\RouterException Error while matching the url.
     */
    // TODO : eventuellement créer une interface pour l'exception, comme par exemple l'implémentation du PSR11 pour le container !!! https://www.php-fig.org/psr/psr-11/
    public function match(ServerRequestInterface $request): MatchingResult;
}

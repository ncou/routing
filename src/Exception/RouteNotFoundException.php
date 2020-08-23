<?php

declare(strict_types=1);

namespace Chiron\Routing\Exception;

class RouteNotFoundException extends RouterException
{
    public function __construct(string $routeName = '', int $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Named route "%s" can\'t be found in the router', $routeName);

        parent::__construct($message, $code, $previous);
    }
}

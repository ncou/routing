<?php

declare(strict_types=1);

namespace Chiron\Routing\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class RedirectController
{
    /**
     * @var ResponseFactoryInterface
     */
    private $factory;

    /**
     * @param ResponseFactoryInterface $factory
     */
    public function __construct(ResponseFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * The parameters $destination and $status are retrieved from the Request attributes.
     *
     * @param string $destination
     * @param int    $status
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    // TODO : forcer un cast (string) sur la destination ce qui permettra de passer soit un string soit un objet UriInterface qui sera casté en string.
    public function redirect(string $destination, int $status): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        return $response->withHeader('Location', $destination);
    }
}

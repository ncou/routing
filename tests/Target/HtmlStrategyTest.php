<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing\Target;

use Chiron\Http\Middleware\DispatcherMiddleware;
use Chiron\Http\Middleware\RoutingMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

// TODO : classe à finir de compléter !!!!!!!!!!

class HtmlStrategyTest extends TestCase
{
    public function testRouteStrategyWithRequestTypeHintting()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $routeCallback = function (ServerRequestInterface $request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Kernel();
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->getRouter()->getRouteCollector()->get('/foo', $routeCallback);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Controller "Closure" requires that you provide a value for the "$request" argument (because there is no default value or because there is a non optional argument after this one).
     */
    public function testRouteStrategyWithoutRequestTypeHintting()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $routeCallback = function ($request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Kernel();
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->getRouter()->getRouteCollector()->get('/foo', $routeCallback);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Controller "Closure" requires that you provide a value for the "$request" argument (because there is no default value or because there is a non optional argument after this one).
     */
    public function testRouteStrategyWithBadTypeHintting()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $routeCallback = function (int $request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Kernel();
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->getRouter()->getRouteCollector()->get('/foo', $routeCallback);

        $response = $app->handle($request);
    }

    public function testRouteStrategyWithScalarTypeHintting()
    {
        $request = new ServerRequest('GET', new Uri('/foo/123/bar/true/2.3'));

        $routeCallback = function (ServerRequestInterface $request, int $id, string $name, bool $isRegistered, float $floatNumber) {
            $response = new Response();

            return $response->write($id . $name . ($isRegistered ? 'true' : 'false') . $floatNumber);
        };

        $app = new Kernel();
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->getRouter()->getRouteCollector()->get('/foo/{id}/{name}/{isRegistered}/{floatNumber}', $routeCallback);

        $response = $app->handle($request);

        $this->assertEquals('123bartrue2.3', (string) $response->getBody());
    }

    public function testRouteStrategyWithScalarTypeHinttingAndDefaultValue()
    {
        $request = new ServerRequest('GET', new Uri('/foo/'));

        $routeCallback = function (ServerRequestInterface $request, int $id = 123, string $name = 'bar', bool $isRegistered = true, float $floatNumber = 2.3) {
            $response = new Response();

            return $response->write($id . $name . ($isRegistered ? 'true' : 'false') . $floatNumber);
        };

        $app = new Kernel();
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->getRouter()->getRouteCollector()->get('/foo/[{id}/{name}/{isRegistered}/{floatNumber}]', $routeCallback);

        $response = $app->handle($request);

        $this->assertEquals('123bartrue2.3', (string) $response->getBody());
    }

    public function testRouteStrategyStoreParamsInRequestAttributes()
    {
        $request = new ServerRequest('GET', new Uri('/foo/bar'));

        $routeCallback = function (ServerRequestInterface $request) {
            $this->assertEquals('bar', $request->getAttribute('name'));

            return new Response();
        };

        $app = new Kernel();
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->getRouter()->getRouteCollector()->get('/foo/{name}', $routeCallback);

        $app->handle($request);
    }
}

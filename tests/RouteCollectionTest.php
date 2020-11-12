<?php

declare(strict_types=1);

namespace Chiron\Routing\Tests;

use Chiron\Routing\RouteCollection;
use Chiron\Routing\Route;
use PHPUnit\Framework\TestCase;
use Chiron\Container\Container;
use Chiron\Http\Http;
use Chiron\Http\Message\RequestMethod as Method;
use Chiron\Http\Message\StatusCode as Status;
use Chiron\Routing\Exception\RouteNotFoundException;
use ArrayIterator;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Uri;
use Chiron\Views\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Chiron\Views\Engine\PhpRenderer;
use Chiron\Routing\Exception\RouterException;

class RouteCollectionTest extends TestCase
{
    public function testGetFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->get('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::GET);
    }

    public function testHeadFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->head('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::HEAD);
    }

    public function testPostFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->post('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::POST);
    }

    public function testPutFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->put('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::PUT);
    }

    public function testDeleteFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->delete('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::DELETE);
    }

    public function testOptionsFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->options('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::OPTIONS);
    }

    public function testTraceFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->trace('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::TRACE);
    }

    public function testPatchFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->patch('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::PATCH);
    }

    public function testAnyFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->any('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), Method::ANY);
    }

    public function testMapFunction()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->map('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), Method::ANY);
    }

    public function testAddRouteInjectContainer()
    {
        $container = new Container();
        $collection = new RouteCollection($container);
        $route = new Route('/foobar');

        $this->assertFalse($route->hasContainer());

        $collection->addRoute($route);

        $this->assertTrue($route->hasContainer());
    }

    public function testMapInjectContainer()
    {
        $container = new Container();
        $collection = new RouteCollection($container);

        $route = $collection->map('/foobar');

        $this->assertTrue($route->hasContainer());
    }

    public function testPermanentRedirectThrowException()
    {
        $collection = new RouteCollection(new Container());
        $bad_uri = 123;

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Redirection allowed only for string or UriInterface uris.');

        $route = $collection->permanentRedirect($bad_uri, '/foobar');
    }

    public function testRedirectThrowException()
    {
        $collection = new RouteCollection(new Container());
        $bad_uri = 123;

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Redirection allowed only for string or UriInterface uris.');

        $route = $collection->redirect($bad_uri, '/foobar');
    }

    public function testViewThrowException()
    {
        $collection = new RouteCollection(new Container());
        $bad_uri = 123;

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('View rendering allowed only for string or UriInterface uris.');

        $route = $collection->view($bad_uri, 'my_view_template', ['param1' => 'value1']);
    }

    /**
     * @dataProvider uriProvider
     */
    public function testPermanentRedirect($uri)
    {
        $container = new Container();
        $container->bind(ResponseFactoryInterface::class, Psr17Factory::class);

        $collection = new RouteCollection($container);

        $route = $collection->permanentRedirect($uri, '/foobar');

        $request = new ServerRequest('GET', '/foo');
        $response = $route->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($response->getHeader('Location'), (array) '/foobar');
        $this->assertEquals($response->getStatusCode(), Status::MOVED_PERMANENTLY);
    }

    /**
     * @dataProvider uriProvider
     */
    public function testRedirect($uri)
    {
        $container = new Container();
        $container->bind(ResponseFactoryInterface::class, Psr17Factory::class);

        $collection = new RouteCollection($container);

        $route = $collection->redirect($uri, '/foobar');

        $request = new ServerRequest('GET', '/foo');
        $response = $route->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($response->getHeader('Location'), (array) '/foobar');
        $this->assertEquals($response->getStatusCode(), Status::FOUND);
    }

    /**
     * @dataProvider uriProvider
     */
    public function testView($uri)
    {
        $container = new Container();
        $container->bind(ResponseFactoryInterface::class, Psr17Factory::class);

        $renderer = new PhpRenderer();
        $renderer->addPath(__DIR__ . '/Fixtures');
        $container->bind(TemplateRendererInterface::class, $renderer);

        $collection = new RouteCollection($container);

        $route = $collection->view($uri, 'my_view_template', ['param1' => 'value1']);

        $request = new ServerRequest('GET', '/foo');
        $response = $route->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($response->getHeader('Content-Type'), (array) 'text/html');
        $this->assertEquals((string) $response->getBody(), 'value1');
        $this->assertEquals($response->getStatusCode(), Status::OK);
    }

    public function uriProvider(): array
    {
        return [
            ['/foo'],
            [new Uri('/foo')],
        ];
    }

    public function testGetNamedRoute()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->map('/foobar')->name('foo');

        $this->assertSame($route, $collection->getNamedRoute('foo'));
    }

    public function testGetNamedRouteThrowException()
    {
        $collection = new RouteCollection(new Container());

        $route = $collection->map('/foobar')->name('foo');

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Named route "non_existing_name" can\'t be found in the route collection.');

        $collection->getNamedRoute('non_existing_name');
    }

    public function testGetRoutes()
    {
        $collection = new RouteCollection(new Container());

        $this->assertSame([], $collection->getRoutes());

        $route = $collection->map('/foobar');

        $this->assertSame([$route], $collection->getRoutes());
    }

    public function testIterator()
    {
        $collection = new RouteCollection(new Container());

        $this->assertInstanceOf(ArrayIterator::class, $collection->getIterator());
        $this->assertSame([], $collection->getIterator()->getArrayCopy());

        $route = $collection->map('/foobar');

        $this->assertSame([$route], $collection->getIterator()->getArrayCopy());
    }

    public function testCount()
    {
        $collection = new RouteCollection(new Container());

        $this->assertCount(0, $collection);

        $route = $collection->map('/foobar');

        $this->assertCount(1, $collection);
    }
}

<?php

declare(strict_types=1);

namespace Chiron\Routing\Tests;

use ArrayIterator;
use Chiron\Container\Container;
use Chiron\Http\Message\RequestMethod as Method;
use Chiron\Http\Message\StatusCode as Status;
use Chiron\Routing\Exception\RouteNotFoundException;
use Chiron\Routing\Exception\RouterException;
use Chiron\Routing\Route;
use Chiron\Routing\Map;
use Chiron\Views\Engine\PhpRenderer;
use Chiron\Views\TemplateRendererInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

// TODO : créer une méthode createMap() avec en possibilité de paramétre un container (si il est null par défault on fait un new Container()) et un basepath par défaut à null, ca évitera d'avoir X fois un new Map() dans cette classe de tests !!!!

class MapTest extends TestCase
{
    public function testPrefixEmpty()
    {
        $collection = new Map('');
        $collection->setContainer(new Container());

        $route = $collection->map('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
    }

    public function testPrefixSlash()
    {
        $collection = new Map('/');
        $collection->setContainer(new Container());

        $route = $collection->map('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
    }

    public function testPrefixUsingMapFunction()
    {
        $collection = new Map('/basepath');
        $collection->setContainer(new Container());

        $route = $collection->map('/foobar');

        $this->assertEquals($route->getPath(), '/basepath/foobar');
    }

    public function testPrefixUsingAddRouteFunction()
    {
        $collection = new Map('/basepath');
        $collection->setContainer(new Container());
        $route = new Route('/foobar');

        $collection->addRoute($route);

        $this->assertEquals($route->getPath(), '/basepath/foobar');
    }

    public function testGetFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->get('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::GET);
    }

    public function testHeadFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->head('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::HEAD);
    }

    public function testPostFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->post('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::POST);
    }

    public function testPutFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->put('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::PUT);
    }

    public function testDeleteFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->delete('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::DELETE);
    }

    public function testOptionsFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->options('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::OPTIONS);
    }

    public function testTraceFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->trace('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::TRACE);
    }

    public function testPatchFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->patch('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), (array) Method::PATCH);
    }

    public function testAnyFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->any('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), Method::ANY);
    }

    public function testMapFunction()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->map('/foobar');

        $this->assertEquals($route->getPath(), '/foobar');
        $this->assertEquals($route->getAllowedMethods(), Method::ANY);
    }

    public function testAddRouteInjectContainer()
    {
        $container = new Container();
        $collection = new Map();
        $collection->setContainer($container);
        $route = new Route('/foobar');

        $this->assertFalse($route->hasContainer());

        $collection->addRoute($route);

        $this->assertTrue($route->hasContainer());
    }

    public function testMapInjectContainer()
    {
        $container = new Container();
        $collection = new Map();
        $collection->setContainer($container);

        $route = $collection->map('/foobar');

        $this->assertTrue($route->hasContainer());
    }

    public function testPermanentRedirectThrowException()
    {
        $collection = new Map();
        $collection->setContainer(new Container());
        $bad_uri = 123;

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Redirection allowed only for string or UriInterface uris.');

        $route = $collection->permanentRedirect($bad_uri, '/foobar');
    }

    public function testRedirectThrowException()
    {
        $collection = new Map();
        $collection->setContainer(new Container());
        $bad_uri = 123;

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Redirection allowed only for string or UriInterface uris.');

        $route = $collection->redirect($bad_uri, '/foobar');
    }

    public function testViewThrowException()
    {
        $collection = new Map();
        $collection->setContainer(new Container());
        $bad_uri = 123;

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('View rendering allowed only for string or UriInterface uris.');

        $route = $collection->view($bad_uri, 'my_view_template', ['name' => 'Foobar']);
    }

    /**
     * @dataProvider uriProvider
     */
    public function testPermanentRedirect($uri)
    {
        $container = new Container();
        $container->bind(ResponseFactoryInterface::class, Psr17Factory::class);

        $collection = new Map();
        $collection->setContainer($container);

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

        $collection = new Map();
        $collection->setContainer($container);

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

        $collection = new Map();
        $collection->setContainer($container);

        $route = $collection->view($uri, 'my_view_template', ['name' => 'Foobar']);

        $request = new ServerRequest('GET', '/foo');
        $response = $route->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($response->getHeader('Content-Type'), (array) 'text/html');
        $this->assertEquals((string) $response->getBody(), 'Foobar');
        $this->assertEquals($response->getStatusCode(), Status::OK);
    }

    public function uriProvider(): array
    {
        return [
            ['/foo'],
            [new Uri('/foo')],
        ];
    }

    public function testGroups(): void
    {
        $r = new Map();
        $r->setContainer(new Container());

        $r->delete('/delete');
        $r->get('/get');
        $r->head('/head');
        $r->patch('/patch');
        $r->post('/post');
        $r->put('/put');
        $r->options('/options');

        $r->group('/group-one', static function (Map $r): void {
            $r->delete('/delete');
            $r->get('/get');
            $r->head('/head');
            $r->patch('/patch');
            $r->post('/post');
            $r->put('/put');
            $r->options('/options');

            $r->group('/group-two', static function (Map $r): void {
                $r->delete('/delete');
                $r->get('/get');
                $r->head('/head');
                $r->patch('/patch');
                $r->post('/post');
                $r->put('/put');
                $r->options('/options');
            });
        });

        $r->group('/admin', static function (Map $r): void {
            $r->get('-some-info');
        });
        $r->group('/admin-', static function (Map $r): void {
            $r->get('more-info');
        });

        $r->group('/slash/', static function (Map $r): void {
            $r->get('/slash/');
        });

        $r->group('/slash', static function (Map $r): void {
            $r->get('slash');
        });

        $expected = [
            ['DELETE', '/delete'],
            ['GET', '/get'],
            ['HEAD', '/head'],
            ['PATCH', '/patch'],
            ['POST', '/post'],
            ['PUT', '/put'],
            ['OPTIONS', '/options'],
            ['DELETE', '/group-one/delete'],
            ['GET', '/group-one/get'],
            ['HEAD', '/group-one/head'],
            ['PATCH', '/group-one/patch'],
            ['POST', '/group-one/post'],
            ['PUT', '/group-one/put'],
            ['OPTIONS', '/group-one/options'],
            ['DELETE', '/group-one/group-two/delete'],
            ['GET', '/group-one/group-two/get'],
            ['HEAD', '/group-one/group-two/head'],
            ['PATCH', '/group-one/group-two/patch'],
            ['POST', '/group-one/group-two/post'],
            ['PUT', '/group-one/group-two/put'],
            ['OPTIONS', '/group-one/group-two/options'],
            ['GET', '/admin/-some-info'],
            ['GET', '/admin-/more-info'],
            ['GET', '/slash/slash/'],
            ['GET', '/slash/slash'],
        ];

        foreach ($r->getRoutes() as $index => $route) {
            self::assertSame((array) $expected[$index][0], $route->getAllowedMethods());
            self::assertSame($expected[$index][1], $route->getPath());
        }
    }

    public function testGetRoute()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->map('/foobar')->name('foo');

        $this->assertSame($route, $collection->getRoute('foo'));
    }

    public function testGetRouteThrowException()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $route = $collection->map('/foobar')->name('foo');

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Named route "non_existing_name" can\'t be found in the route collection.');

        $collection->getRoute('non_existing_name');
    }

    public function testhasRoute()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $this->assertFalse($collection->hasRoute('foo'));

        $route = $collection->map('/foobar')->name('foo');

        $this->assertTrue($collection->hasRoute('foo'));
    }

    public function testGetRoutes()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $this->assertSame([], $collection->getRoutes());

        $route = $collection->map('/foobar');

        $this->assertSame([$route], $collection->getRoutes());
    }

    public function testIterator()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $this->assertInstanceOf(ArrayIterator::class, $collection->getIterator());
        $this->assertSame([], $collection->getIterator()->getArrayCopy());

        $route = $collection->map('/foobar');

        $this->assertSame([$route], $collection->getIterator()->getArrayCopy());
    }

    public function testCount()
    {
        $collection = new Map();
        $collection->setContainer(new Container());

        $this->assertCount(0, $collection);

        $route = $collection->map('/foobar');

        $this->assertCount(1, $collection);
    }
}

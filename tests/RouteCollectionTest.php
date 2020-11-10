<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing;

use Chiron\Routing\RouteUrlGenerator;
use Chiron\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouteCollectionTest extends TestCase
{
    /**
     * Base path is ignored by relativeUrlFor().
     */
    public function testRelativeUrlFor()
    {
        $router = new Router();

        $router->setBasePath('/base/path');
        $pattern = '/hello/{first:\w+}/{last}';

        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $this->assertEquals(
            '/hello/josh/lockhart',
            $router->relativeUrlFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testUrlForWithNoBasePath()
    {
        $router = new Router();

        $router->setBasePath('');
        $pattern = '/hello/{first:\w+}/{last}';

        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $this->assertEquals(
            '/hello/josh/lockhart',
            $router->urlFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testUrlForWithBasePath()
    {
        $router = new Router();

        $pattern = '/hello/{first:\w+}/{last}';

        $router->setBasePath('/base/path');
        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $this->assertEquals(
            '/base/path/hello/josh/lockhart',
            $router->urlFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testUrlForWithOptionalParameters()
    {
        $router = new Router();

        $pattern = '/archive/{year}[/{month}[/d/{day}]]';

        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $this->assertEquals(
            '/archive/2015',
            $router->urlFor('foo', ['year' => '2015'])
        );
        $this->assertEquals(
            '/archive/2015/7',
            $router->urlFor('foo', ['year' => '2015', 'month' => 7])
        );
        $this->assertEquals(
            '/archive/2015/12/d/19',
            $router->urlFor('foo', ['year' => '2015', 'month' => '12', 'day' => '19'])
        );
    }

    public function testUrlForWithQueryParameters()
    {
        $router = new Router();

        $pattern = '/hello/{name}';

        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $this->assertEquals(
            '/hello/josh?a=b&c=d',
            $router->urlFor('foo', ['name' => 'josh'], ['a' => 'b', 'c' => 'd'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Missing data for URL segment: first
     */
    public function testUrlForWithMissingSegmentData()
    {
        $router = new Router();

        $pattern = '/hello/{first}/{last}';

        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $router->urlFor('foo', ['last' => 'lockhart']);
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Named route does not exist for name:
     */
    public function testUrlForRouteNotExists()
    {
        $router = new Router();

        $pattern = '/hello/{first}/{last}';

        $route = $router->getRouteCollector()->map($pattern, 'callable');
        $route->setName('foo');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $router->urlFor('bar', ['first' => 'josh', 'last' => 'lockhart']);
    }

    // TODO : améliorer les tests, il faut que les queryParams soient calculés automatiquement. regarder les tests mis en commentaire !!!  https://github.com/laravel/framework/blob/5.8/tests/Routing/RoutingUrlGeneratorTest.php#L168
    public function testBasicRouteGeneration()
    {
        $router = new Router();
        $callable = 'callable';

        /*
         * Empty Named Route
         */
        $route = $router->getRouteCollector()->get('/', $callable)->setName('plain');

        /*
         * Named Routes
         */
        $route = $router->getRouteCollector()->get('foo/bar', $callable)->setName('foo');
        /*
         * Parameters...
         */
        $route = $router->getRouteCollector()->get('foo/bar/{baz}/breeze/{boom}', $callable)->setName('bar');
        /*
         * Single Parameter...
         */
        $route = $router->getRouteCollector()->get('foo/bar/{baz}', $callable)->setName('foobar');
        /*
         * Non ASCII routes
         */
        $route = $router->getRouteCollector()->get('foo/bar/åαф/{baz}', $callable)->setName('foobarbaz');
        /*
         * Fragments
         */
        $route = $router->getRouteCollector()->get('foo/bar#derp', $callable)->setName('fragment');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $this->assertEquals('/', $router->urlFor('plain', []));
//        $this->assertEquals('/?foo=bar', $urlGenerator->urlFor('plain', ['foo' => 'bar']));

        $this->assertEquals('/foo/bar', $router->urlFor('foo'));
        $this->assertEquals('/foo/bar', $router->urlFor('foo', []));

//        $this->assertEquals('/foo/bar?foo=bar', $urlGenerator->urlFor('foo', ['foo' => 'bar']));
//        $this->assertEquals('/foo/bar/taylor/breeze/otwell?fly=wall', $urlGenerator->urlFor('bar', ['taylor', 'otwell', 'fly' => 'wall']));
//        $this->assertEquals('/foo/bar/otwell/breeze/taylor?fly=wall', $urlGenerator->urlFor('bar', ['boom' => 'taylor', 'baz' => 'otwell', 'fly' => 'wall']));
//        $this->assertEquals('/foo/bar/2', $urlGenerator->urlFor('foobar', [2]));
//        $this->assertEquals('/foo/bar/taylor', $urlGenerator->urlFor('foobar', ['taylor']));
//        $this->assertEquals('/foo/bar/taylor/breeze/otwell?fly=wall', $urlGenerator->urlFor('bar', ['taylor', 'otwell', 'fly' => 'wall']));
//        $this->assertEquals('/foo/bar/taylor/breeze/otwell?wall&woz', $urlGenerator->urlFor('bar', ['wall', 'woz', 'boom' => 'otwell', 'baz' => 'taylor']));
//        $this->assertEquals('/foo/bar/taylor/breeze/otwell?wall&woz', $urlGenerator->urlFor('bar', ['taylor', 'otwell', 'wall', 'woz']));
        $this->assertEquals('/foo/bar/%C3%A5%CE%B1%D1%84/%C3%A5%CE%B1%D1%84', $router->urlFor('foobarbaz', ['baz' => 'åαф']));
        $this->assertEquals('/foo/bar#derp', $router->urlFor('fragment', [], []));
        $this->assertEquals('/foo/bar?foo=bar#derp', $router->urlFor('fragment', [], ['foo' => 'bar']));
        $this->assertEquals('/foo/bar?baz=%C3%A5%CE%B1%D1%84#derp', $router->urlFor('fragment', [], ['baz' => 'åαф']));
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage did not match the regex
     */
    public function testRouteGenerationWrongRegex()
    {
        $router = new Router();
        $callable = 'callable';

        $route = $router->getRouteCollector()->get('/test/{ param : \d{1,9} }', $callable)->setName('numeric');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $router->urlFor('numeric', ['param' => 1234567890]);
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage did not match the regex
     */
    public function testRouteGenerationWrongRegex_2()
    {
        $router = new Router();
        $callable = 'callable';

        $route = $router->getRouteCollector()->get('/test[/{param}[/{id:[0-9]+}]]', $callable)->setName('numeric');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $router->urlFor('numeric', ['param' => 'foo', 'id' => 'foo']);
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage did not match the regex
     */
    public function testRouteGenerationWrongRegex_3()
    {
        $router = new Router();
        $callable = 'callable';

        $route = $router->getRouteCollector()->get('/{lang:(fr|en)}', $callable)->setName('string');

        $urlGenerator = new RouteUrlGenerator($router->getRouteCollector());

        $router->urlFor('string', ['lang' => 'foo']);
    }
}

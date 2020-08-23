<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing;

use Chiron\Routing\Route;
use Chiron\Routing\RouteGroup;
use Chiron\Routing\Router;
use Chiron\Routing\Strategy\StrategyInterface;
use PHPUnit\Framework\TestCase;

class RouteGroupTest extends TestCase
{
    public function testRouteGroup()
    {
        $router = (new Router())->getRouteCollector();

        $res = $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1');

            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_2';
                })->name('test_2');
                $group->get('/bar', function () {
                    return 'ROUTE_3';
                })->name('test_3');
            });
        });

        $this->assertInstanceOf(RouteGroup::class, $res);
        //$this->assertEquals('/prefix', $res->getPrefix());

        $this->assertEquals('test_1', ($router->getRoutes()[0])->getName());
        $this->assertEquals('/prefix', ($router->getRoutes()[0])->getPath());

        $this->assertEquals('test_2', ($router->getRoutes()[1])->getName());
        $this->assertEquals('/prefix/group/foo', ($router->getRoutes()[1])->getPath());

        $this->assertEquals('test_3', ($router->getRoutes()[2])->getName());
        $this->assertEquals('/prefix/group/bar', ($router->getRoutes()[2])->getPath());
    }

    public function testRouteGroupOfGroup()
    {
        $router = (new Router())->getRouteCollector();

        $routeGroup = $router->group('/prefix', function ($group) {
            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_1';
                })->name('test_1');
            });
        });

        $this->assertEquals('/prefix/group/foo', $router->getNamedRoute('test_1')->getPath());

        $routeGroup->group('/baz', function ($group) {
            $group->group('/qux/', function ($group) {
                $group->get('/user', function () {
                    return 'ROUTE_2';
                })->name('test_2');
            });
        });

        $this->assertEquals('/prefix/baz/qux/user', $router->getNamedRoute('test_2')->getPath());
    }

    public function testRouteOverrideStrategyAndConditions()
    {
        $router = (new Router())->getRouteCollector();
        $strategyMock = $this->createMock(StrategyInterface::class);

        $routeGroup = $router->group('/prefix', function ($group) use ($strategyMock) {
            $group->map('/foo', function () {
                return 'ROUTE_1';
            })->name('test_1')->scheme('http')->host('host')->port(80)->setStrategy($strategyMock);

            $group->group('/group1/', function ($group) {
                $group->group('/group2/', function ($group) {
                    $group->get('/foo', function () {
                        return 'ROUTE_2';
                    })->name('test_2');
                });
            })->scheme('https')->host('host2')->port(81)->setStrategy($strategyMock);
        });

        $route_1 = $router->getNamedRoute('test_1');
        $this->assertEquals('/prefix/foo', $route_1->getPath());
        $this->assertEquals($strategyMock, $route_1->getStrategy());
        $this->assertEquals(80, $route_1->getPort());
        $this->assertEquals('host', $route_1->getHost());
        $this->assertEquals('http', $route_1->getScheme());

        $route_2 = $router->getNamedRoute('test_2');
        $this->assertEquals('/prefix/group1/group2/foo', $route_2->getPath());
        $this->assertEquals($strategyMock, $route_2->getStrategy());
        $this->assertEquals(81, $route_2->getPort());
        $this->assertEquals('host2', $route_2->getHost());
        $this->assertEquals('https', $route_2->getScheme());
    }

    public function testRouteGroupOfGroupWithOverrideHostPortScheme()
    {
        $router = (new Router())->getRouteCollector();

        $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1')->host('baz');

            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_2';
                })->name('test_2');
                $group->get('/bar', function () {
                    return 'ROUTE_3';
                })->name('test_3');
            })->port(8080);
        })->scheme('https');

        $route_1 = $router->getNamedRoute('test_1');
        $this->assertEquals('baz', $route_1->getHost());
        $this->assertEquals(null, $route_1->getPort());
        $this->assertEquals('https', $route_1->getScheme());

        $route_2 = $router->getNamedRoute('test_2');
        $this->assertEquals(null, $route_2->getHost());
        $this->assertEquals(8080, $route_2->getPort());
        $this->assertEquals('https', $route_2->getScheme());

        $route_3 = $router->getNamedRoute('test_3');
        $this->assertEquals(null, $route_3->getHost());
        $this->assertEquals(8080, $route_3->getPort());
        $this->assertEquals('https', $route_3->getScheme());
    }

    public function testRouteGroupWithOverrideMiddleware()
    {
        $router = (new Router())->getRouteCollector();

        $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1')->middleware('MIDDLEWARE_1');

            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_2';
                })->name('test_2')->middleware('MIDDLEWARE_2');
                $group->get('/bar', function () {
                    return 'ROUTE_3';
                })->name('test_3');
            })->middleware('MIDDLEWARE_3');
        })->middleware('MIDDLEWARE_4');

        $route_1 = $router->getNamedRoute('test_1');
        $this->assertEquals(['MIDDLEWARE_4', 'MIDDLEWARE_1'], $route_1->getMiddlewareStack());

        $route_2 = $router->getNamedRoute('test_2');
        $this->assertEquals(['MIDDLEWARE_4', 'MIDDLEWARE_3', 'MIDDLEWARE_2'], $route_2->getMiddlewareStack());

        $route_3 = $router->getNamedRoute('test_3');
        $this->assertEquals(['MIDDLEWARE_4', 'MIDDLEWARE_3'], $route_3->getMiddlewareStack());
    }

    public function testRouteNestedGroupWithOverrideMiddleware()
    {
        $router = (new Router())->getRouteCollector();

        $grp = $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1')->middleware('MIDDLEWARE_1');
        })->middleware('MIDDLEWARE_4a');

        $grp->middleware('MIDDLEWARE_4b');

        $grp->group('/group/', function ($group) {
            $group->get('/foo', function () {
                return 'ROUTE_2';
            })->name('test_2')->middleware('MIDDLEWARE_2');
            $group->get('/bar', function () {
                return 'ROUTE_3';
            })->name('test_3');
        })->middleware('MIDDLEWARE_3');

        $route_1 = $router->getNamedRoute('test_1');
        $this->assertEquals(['MIDDLEWARE_4a', 'MIDDLEWARE_4b', 'MIDDLEWARE_1'], $route_1->getMiddlewareStack());

        $route_2 = $router->getNamedRoute('test_2');
        $this->assertEquals(['MIDDLEWARE_4a', 'MIDDLEWARE_4b', 'MIDDLEWARE_3', 'MIDDLEWARE_2'], $route_2->getMiddlewareStack());

        $route_3 = $router->getNamedRoute('test_3');
        $this->assertEquals(['MIDDLEWARE_4a', 'MIDDLEWARE_4b', 'MIDDLEWARE_3'], $route_3->getMiddlewareStack());
    }

    public function testRouteMiddlewareTrait()
    {
        $router = (new Router())->getRouteCollector();
        $group = $router->group('/prefix', function () {
        });

        $this->assertEquals([], $group->getMiddlewareStack());

        $group->middleware('baz');

        $this->assertEquals('baz', $group->getMiddlewareStack()[0]);

        $group->prependMiddleware('qux');

        $this->assertEquals('qux', $group->getMiddlewareStack()[0]);
    }

    public function testRouteConditionTrait()
    {
        $router = (new Router())->getRouteCollector();
        $group = $router->group('/prefix', function () {
        });

        $this->assertEquals(null, $group->getHost());
        $this->assertEquals(null, $group->getScheme());
        $this->assertEquals(null, $group->getPort());

        $group->setHost('host_1');
        $this->assertEquals('host_1', $group->getHost());

        $group->host('host_2');
        $this->assertEquals('host_2', $group->getHost());

        $group->setScheme('http');
        $this->assertEquals('http', $group->getScheme());

        $group->scheme('https');
        $this->assertEquals('https', $group->getScheme());

        $group->requireHttp();
        $this->assertEquals('http', $group->getScheme());

        $group->requireHttps();
        $this->assertEquals('https', $group->getScheme());

        $group->setPort(8080);
        $this->assertEquals(8080, $group->getPort());

        $group->port(8181);
        $this->assertEquals(8181, $group->getPort());
    }

    public function testRouteStrategyTrait()
    {
        $router = (new Router())->getRouteCollector();
        $group = $router->group('/prefix', function () {
        });

        $this->assertEquals(null, $group->getStrategy());

        $strategyMock = $this->createMock(StrategyInterface::class);
        $group->setStrategy($strategyMock);

        $this->assertEquals($strategyMock, $group->getStrategy());

        $group->strategy($strategyMock);

        $this->assertEquals($strategyMock, $group->getStrategy());
    }

    public function httpMethods()
    {
        return [['get'], ['post'], ['put'], ['patch'], ['delete'], ['head'], ['options'], ['trace']];
    }

    /**
     * Asserts that the collection can map and return a route object.
     *
     * @dataProvider httpMethods
     */
    public function testRouteCollectionTraitHttpMethods($method)
    {
        $router = (new Router())->getRouteCollector();

        $group = $router->group('/prefix', function ($group) use ($method) {
            $group->{$method}('/', 'foobar');
        });

        $routes = $router->getRoutes();

        $this->assertSame(1, count($routes));

        $route = $router->getRoutes()[0];

        $this->assertSame([strtoupper($method)], $route->getAllowedMethods());
    }

    public function testRouteCollectionTraitMapAndAny()
    {
        $router = (new Router())->getRouteCollector();
        $group = $router->group('/prefix', function ($group) {
            $group->map('/', 'foobar');
            $group->any('/', 'foobar');
        });

        $routes = $router->getRoutes();

        $this->assertSame(2, count($routes));

        $route_1 = $router->getRoutes()[0];
        $this->assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'], $route_1->getAllowedMethods());

        $route_2 = $router->getRoutes()[1];
        $this->assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'], $route_2->getAllowedMethods());
    }
}

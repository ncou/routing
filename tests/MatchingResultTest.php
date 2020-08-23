<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing;

use Chiron\Routing\Route;
use Chiron\Routing\MatchingResult;
use Error;
use PHPUnit\Framework\TestCase;

class MatchingResultTest extends TestCase
{
    /**
     * @expectedException Error
     * @expectedExceptionMessage Call to private Chiron\Routing\MatchingResult::__construct()
     */
    public function testClassMatchingResultCantBeInstancied()
    {
        $result = new MatchingResult();
    }

    public function testRouteNameIsNotRetrievable()
    {
        $result = MatchingResult::fromRouteFailure([]);
        $this->assertFalse($result->getMatchedRouteName());
    }

    public function testRouteMiddlewareStackIsNotRetrievable()
    {
        $result = MatchingResult::fromRouteFailure([]);
        $this->assertFalse($result->getMatchedRouteMiddlewareStack());
    }

    // TODO : à corriger
    public function testRouteFailureRetrieveAllHttpMethods()
    {
        $result = MatchingResult::fromRouteFailure(MatchingResult::HTTP_METHOD_ANY);
        $this->assertSame(MatchingResult::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    public function testRouteFailureRetrieveHttpMethods()
    {
        $result = MatchingResult::fromRouteFailure([]);
        $this->assertSame([], $result->getAllowedMethods());
    }

    public function testRouteMatchedParams()
    {
        $params = ['foo' => 'bar'];
        $route = $this->prophesize(Route::class);
        $result = MatchingResult::fromRoute($route->reveal(), $params);
        $this->assertSame($params, $result->getMatchedParams());
    }

    public function testRouteMethodFailure()
    {
        $result = MatchingResult::fromRouteFailure(['GET']);
        $this->assertTrue($result->isMethodFailure());
    }

    public function testRouteSuccessMethodFailure()
    {
        $params = ['foo' => 'bar'];
        $route = $this->prophesize(Route::class);
        $result = MatchingResult::fromRoute($route->reveal(), $params);
        $this->assertFalse($result->isMethodFailure());
    }

    public function testFromRouteShouldComposeRouteInResult()
    {
        $route = $this->prophesize(Route::class);
        $result = MatchingResult::fromRoute($route->reveal(), ['foo' => 'bar']);
        $this->assertInstanceOf(MatchingResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame($route->reveal(), $result->getMatchedRoute());

        return ['route' => $route, 'result' => $result];
    }

    /**
     * @depends testFromRouteShouldComposeRouteInResult
     *
     * @param array $data
     */
    public function testAllAccessorsShouldReturnExpectedDataWhenResultCreatedViaFromRoute(array $data)
    {
        $result = $data['result'];
        $route = $data['route'];
        $route->getName()->willReturn('route');
        $route->getMiddlewareStack()->willReturn(['middleware']);
        $route->getAllowedMethods()->willReturn(['HEAD', 'OPTIONS', 'GET']);
        $this->assertEquals('route', $result->getMatchedRouteName());
        $this->assertEquals(['middleware'], $result->getMatchedRouteMiddlewareStack());
        $this->assertEquals(['HEAD', 'OPTIONS', 'GET'], $result->getAllowedMethods());
    }

    public function testRouteFailureWithNoAllowedHttpMethodsShouldReportTrueForIsMethodFailure()
    {
        $result = MatchingResult::fromRouteFailure([]);
        $this->assertTrue($result->isMethodFailure());
    }

    // TODO : à corriger
    public function testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed()
    {
        $result = MatchingResult::fromRouteFailure(MatchingResult::HTTP_METHOD_ANY);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());

        return $result;
    }

    /**
     * @depends testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed
     */
    public function testAllowedMethodsIncludesASingleWildcardEntryWhenAllMethodsAllowedForFailureResult(
        MatchingResult $result
    ) {
        $this->assertSame(MatchingResult::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }
}

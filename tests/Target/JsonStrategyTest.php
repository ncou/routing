<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing\Target;

use ArrayObject;
use Chiron\Application;
use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Kernel;
use Chiron\Routing\Target\JsonStrategy;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use stdClass;

// TODO : classe à finir de compléter !!!!!!!!!!
// TODO : ajouter des tests pour executer la stratégie aprés avoir instancié un objet "Application".
// TODO : ajouter des tests pour vérifier si les paramétres et la request sont bien stockés dans les attributs de la request. cf tests de la classe HtmlStrategy

class JsonStrategyTest extends TestCase
{
    public function testJsonStrategyInitialisation()
    {
        $strategy = new JsonStrategy(new Kernel());

        $data = ['foo' => 'bar'];
        $callback = function () use ($data) {
            return $data;
        };

        $request = new ServerRequest('GET', new Uri('/'));

        $response = $strategy->invokeRouteHandler($callback, [], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertEquals(json_encode($data, JsonStrategy::DEFAULT_JSON_FLAGS), (string) $response->getBody());

        $this->assertEquals(79, $strategy->getEncodingOptions());
    }

    /**
     * @param mixed $data
     *
     * @expectedException \InvalidArgumentException
     *
     * @dataProvider jsonErrorDataProvider
     */
    public function testInvalidArgumentExceptionOnJsonError($data)
    {
        $strategy = new JsonStrategy(new Kernel());

        $callback = function () use ($data) {
            return $data;
        };

        $request = new ServerRequest('GET', new Uri('/'));

        $response = $strategy->invokeRouteHandler($callback, [], $request);
    }

    /**
     * @param mixed $data
     *
     * @dataProvider jsonErrorDataProvider
     */
    public function testGracefullyHandledSomeJsonErrorsWithPartialOutputOnError($data)
    {
        $strategy = new JsonStrategy(new Kernel());
        $strategy->setEncodingOptions($strategy->getEncodingOptions() | JSON_PARTIAL_OUTPUT_ON_ERROR);

        $callback = function () use ($data) {
            return $data;
        };

        $request = new ServerRequest('GET', new Uri('/'));

        $response = $strategy->invokeRouteHandler($callback, [], $request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array
     */
    public function jsonErrorDataProvider()
    {
        // Resources can't be encoded
        $resource = tmpfile();
        // Recursion can't be encoded
        $recursiveObject = new stdClass();
        $objectB = new stdClass();
        $recursiveObject->b = $objectB;
        $objectB->a = $recursiveObject;
        // NAN or INF can't be encoded
        $nan = NAN;

        return [
            [$resource],
            [$recursiveObject],
            [$nan],
        ];
    }

    /**
     * @dataProvider setAndRetrieveDataProvider
     *
     * @param  $data
     */
    public function testSetAndRetrieveData($data): void
    {
        $strategy = new JsonStrategy(new Kernel());

        $callback = function () use ($data) {
            return $data;
        };

        $request = new ServerRequest('GET', new Uri('/'));

        $response = $strategy->invokeRouteHandler($callback, [], $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(json_encode($data, JsonStrategy::DEFAULT_JSON_FLAGS), (string) $response->getBody());
    }

    public function setAndRetrieveDataProvider(): array
    {
        return [
            'JsonSerializable data' => [new JsonSerializableObject()],
            'Array data'            => [['foo' => 'bar']],
            'ArrayObject data'      => [new ArrayObject(['foo' => 'bar'])],
        ];
    }
}

class JsonSerializableObject implements JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}

<?php

declare(strict_types=1);

namespace MezzioTest\Valinor;

use CuyZ\Valinor\Mapper\TreeMapper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Valinor\DefaultMapperBuilderFactory;
use MezzioTest\Valinor\Asset\ExampleMappedObject;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

#[CoversClass(DefaultMapperBuilderFactory::class)]
final class DefaultMapperBuilderFactoryTest extends TestCase
{
    private TreeMapper $mapper;

    #[Override]
    protected function setUp(): void
    {
        $this->mapper = new DefaultMapperBuilderFactory()
            ->__invoke()
            ->mapper();
    }

    public function test_mapper_works_with_raw_input(): void
    {
        $mapped = $this->mapper->map(
            ExampleMappedObject::class,
            [
                'field1' => 3,
                'field2' => 'foo',
            ]
        );

        self::assertSame(3, $mapped->field1);
        self::assertSame('foo', $mapped->field2);
    }

    public function test_maps_request_parsedBody_fields_to_DTO(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);

        $request->method('getParsedBody')
            ->willReturn([
                'field1' => 4,
                'field2' => 'bar',
            ]);

        $mapped = $this->mapper->map(ExampleMappedObject::class, $request);

        self::assertSame(4, $mapped->field1);
        self::assertSame('bar', $mapped->field2);
    }

    public function test_maps_request_with_route_parameters_and_parsedBody_fields_to_DTO(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);

        $request->method('getParsedBody')
            ->willReturn([
                'field1' => 5,
                'field2' => 'bar',
            ]);

        $routingResult = RouteResult::fromRoute(
            new Route('a/route', $this->createStub(MiddlewareInterface::class)),
            [
                'field2' => 'baz',
            ]
        );

        $request->method('getAttribute')
            ->willReturnMap([
                [RouteResult::class, null, $routingResult],
            ]);

        $mapped = $this->mapper->map(ExampleMappedObject::class, $request);

        self::assertSame(5, $mapped->field1);
        self::assertSame('baz', $mapped->field2);
    }

    public function test_mapper_flexibly_converts_input_parameters_by_default(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);

        $request->method('getParsedBody')
            ->willReturn([
                'field1' => '5',
                'field2' => 'tab',
            ]);

        $mapped = $this->mapper->map(ExampleMappedObject::class, $request);

        self::assertSame(5, $mapped->field1);
        self::assertSame('tab', $mapped->field2);
    }

    public function test_mapper_allows_superfulous_keys_by_default(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);

        $request->method('getParsedBody')
            ->willReturn([
                'field1' => '5',
                'field2' => 'tab',
                'field3' => 'taz',
            ]);

        $mapped = $this->mapper->map(ExampleMappedObject::class, $request);

        self::assertSame(5, $mapped->field1);
        self::assertSame('tab', $mapped->field2);
    }
}

<?php

declare(strict_types=1);

namespace MezzioTest\Valinor;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Mezzio\Valinor\MappingErrorProblemDetailsMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(MappingErrorProblemDetailsMiddleware::class)]
final class MappingErrorProblemDetailsMiddlewareTest extends TestCase
{
    public function test_it_will_let_successful_calls_pass_throug(): void
    {
        $next     = $this->createMock(RequestHandlerInterface::class);
        $request  = $this->createStub(ServerRequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $next
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        self::assertSame($response, new MappingErrorProblemDetailsMiddleware()->process($request, $next));
    }

    public function test_it_will_render_422_responses_for_failed_request_mapping(): void
    {
        try {
            (new MapperBuilder())
                ->mapper()
                ->map(
                    new readonly class ("a", 1) {
                        /** @param int<1, max> $b */
                        public function __construct(
                            public string $a,
                            public int $b,
                        ) {
                        }
                    }::class,
                    '{"a": null, "b": -1}'
                );

            self::fail('We should always get a mapping error here: precondition to run the test');
        } catch (MappingError $mappingError) {
        }

        $next    = $this->createMock(RequestHandlerInterface::class);
        $request = $this->createStub(ServerRequestInterface::class);

        $next
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException($mappingError);

        try {
            new MappingErrorProblemDetailsMiddleware()->process($request, $next);

            self::fail('An exception was supposed to be thrown');
        } catch (ProblemDetailsExceptionInterface $caught) {
        }

        self::assertSame(422, $caught->getCode());
        self::assertSame('HTTP request is invalid', $caught->getMessage());
        self::assertSame('https://www.rfc-editor.org/rfc/rfc9110#section-15.5.21', $caught->getType());
        self::assertSame(
            [
                'errors' => [
                    '*root*' => [
                        'Value \'{"a": null, "b": -1}\' does not match `array{a: string, b: int<1, max>}`.',
                    ],
                ],
            ],
            $caught->getAdditionalData(),
        );
        self::assertSame('A total of 1 mapping error(s) were found.', $caught->getDetail());
        self::assertSame($mappingError, $caught->getPrevious());
    }
}

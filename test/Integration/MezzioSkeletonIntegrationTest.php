<?php

declare(strict_types=1);

namespace MezzioTest\Valinor\Integration;

use CuyZ\Valinor\Mapper\Http\FromBody;
use CuyZ\Valinor\Mapper\Http\FromQuery;
use CuyZ\Valinor\Mapper\Http\FromRoute;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Valinor\ConfigProvider;
use Mezzio\Valinor\MappingErrorProblemDetailsMiddleware;
use MezzioTest\Valinor\Integration\Asset\AddToCart;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;
use function fopen;
use function is_resource;

#[CoversNothing]
final class MezzioSkeletonIntegrationTest extends TestCase
{
    public function test_runs_a_real_request_cycle_through_mezzio(): void
    {
        [$app, $mapper] = $this->makeMezzio();

        $app->post('/cart/{sku}', function (ServerRequestInterface $request) use ($mapper): ResponseInterface {
            $input = $mapper->map(AddToCart::class, $request);

            return new JsonResponse([
                'reached_endpoint' => true,
                'sku'              => $input->sku,
                'quantity'         => $input->quantity,
            ]);
        });

        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
              "reached_endpoint": true,
              "sku": "ABC123",
              "quantity": 999
            }
            JSON,
            $app
                ->handle($this->makeRequest(
                    'POST',
                    'https://example.com/cart/ABC123',
                    [],
                    'quantity=999',
                ))
                ->getBody()
                ->__toString(),
        );
    }

    public function test_maps_body_query_and_route_values_by_default(): void
    {
        [$app, $mapper] = $this->makeMezzio();

        $example = new readonly class () {
            public function __construct(
                public string $parameter1 = 'parameter1',
                public string $parameter2 = 'parameter2',
                public string $parameter3 = 'parameter3',
                public string $parameter4 = 'parameter4',
                public string $parameter5 = 'parameter5',
                public string $parameter6 = 'parameter6',
            ) {
            }
        };

        $app->post(
            '/endpoint/{parameter5}/{parameter6}',
            static fn(ServerRequestInterface $request): ResponseInterface
                => new JsonResponse($mapper->map($example::class, $request)),
        );

        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
              "parameter1": "body1",
              "parameter2": "body2",
              "parameter3": "query1",
              "parameter4": "query2",
              "parameter5": "route1",
              "parameter6": "route2"
            }
            JSON,
            $app
                ->handle($this->makeRequest(
                    'POST',
                    'https://example.com/endpoint/route1/route2',
                    [
                        'parameter3' => 'query1',
                        'parameter4' => 'query2',
                    ],
                    'parameter1=body1&parameter2=body2',
                ))
                ->getBody()
                ->__toString(),
        );
    }

    public function test_maps_conflicting_parameters_to_their_respectively_explicitly_mapped_fields(): void
    {
        [$app, $mapper] = $this->makeMezzio();

        $example = new readonly class () {
            public function __construct(
                #[FromBody]
                public string $parameter1 = 'parameter1',
                #[FromQuery]
                public string $parameter2 = 'parameter2',
                #[FromRoute]
                public string $parameter3 = 'parameter3',
            ) {
            }
        };

        $app->post(
            '/endpoint/{parameter1}/{parameter2}/{parameter3}',
            static fn(ServerRequestInterface $request): ResponseInterface
                => new JsonResponse($mapper->map($example::class, $request)),
        );

        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
              "parameter1": "body1",
              "parameter2": "query2",
              "parameter3": "route3"
            }
            JSON,
            $app
                ->handle($this->makeRequest(
                    'POST',
                    'https://example.com/endpoint/route1/route2/route3',
                    [
                        'parameter1' => 'query1',
                        'parameter2' => 'query2',
                        'parameter3' => 'query3',
                    ],
                    'parameter1=body1&parameter2=body2&parameter3=body3',
                ))
                ->getBody()
                ->__toString(),
        );
    }

    public function test_unknown_parameters_are_ignored_by_default(): void
    {
        [$app, $mapper] = $this->makeMezzio();

        $example = new readonly class () {
            public function __construct(
                public string $parameter1 = 'parameter1',
                public string $parameter2 = 'parameter2',
                public string $parameter3 = 'parameter3',
            ) {
            }
        };

        $app->post(
            '/endpoint/{unknownParameter1}/{unknownParameter2}',
            static fn(ServerRequestInterface $request): ResponseInterface
                => new JsonResponse($mapper->map($example::class, $request)),
        );

        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
              "parameter1": "parameter1",
              "parameter2": "parameter2",
              "parameter3": "parameter3"
            }
            JSON,
            $app
                ->handle($this->makeRequest(
                    'POST',
                    'https://example.com/endpoint/route1/route2',
                    [
                        'unknownParameter3' => 'query1',
                        'unknownParameter4' => 'query2',
                    ],
                    'unknownParameter5=body1&unknownParameter6=body2',
                ))
                ->getBody()
                ->__toString(),
        );
    }

    public function test_fails_mapping_on_conflicting_not_explicitly_attribute_mapped_inputs(): void
    {
        [$app, $mapper] = $this->makeMezzio();

        $example = new readonly class () {
            public function __construct(
                public string $parameter = 'parameter',
            ) {
            }
        };

        $exception = null;

        $app->post(
            '/endpoint/{parameter}',
            static function (ServerRequestInterface $request) use ($example, $mapper, &$exception): ResponseInterface {
                try {
                    $mapper->map($example::class, $request);
                } catch (MappingError $error) {
                    $exception = $error;
                }

                self::fail('The mapper should\'ve thrown an exception');
            }
        );

        self::assertSame(
            500,
            $app
                ->handle($this->makeRequest(
                    'POST',
                    'https://example.com/endpoint/from-route',
                    [
                        'parameter' => 'from-query',
                    ],
                    'parameter=from-body',
                ))
                ->getStatusCode()
        );
        self::assertInstanceOf(MappingError::class, $exception);
    }

    public function test_mapping_error_problem_details_middleware_dispatch_problem_details_exception_properly(): void
    {
        [$app, $mapper] = $this->makeMezzio();

        $example = new readonly class () {
            public function __construct(
                public int $intParameter = 42,
            ) {
            }
        };

        $app->post(
            '/endpoint/{intParameter}',
            static function (ServerRequestInterface $request) use ($example, $mapper): ResponseInterface {
                $mapper->map($example::class, $request);

                self::fail('The mapper should\'ve thrown an exception');
            }
        );

        $response = $app
            ->handle($this->makeRequest(
                'POST',
                'https://example.com/endpoint/some-string-that-is-not-an-int',
                [],
                '',
            ));

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            '{"errors":{"intParameter":["Value \'some-string-that-is-not-an-int\' is not a valid integer."]},'
            . '"title":"HTTP request is invalid","type":"https://www.rfc-editor.org/rfc/rfc9110#section-15.5.21",'
            . '"status":422,"detail":"A total of 1 mapping error(s) were found."}',
            $response->getBody()->__toString()
        );
    }

    /** @param array<string, mixed> $queryParameters */
    private function makeRequest(
        string $method,
        string $url,
        array $queryParameters,
        string $requestBody,
        string $contentTypeHeader = 'application/x-www-form-urlencoded',
    ): ServerRequestInterface {
        $bodyStream = fopen('php://memory', 'rw+');

        assert(is_resource($bodyStream));

        $request = new ServerRequest(
            [],
            [],
            $url,
            $method,
            $bodyStream,
            ['Content-Type' => $contentTypeHeader],
            [],
            $queryParameters,
        );

        $request->getBody()->write($requestBody);

        return $request;
    }

    /**
     * @return array{Application, TreeMapper}
     */
    private function makeMezzio(): array
    {
        /** @var array{dependencies: array{}} $config */
        $config = new ConfigAggregator([
            \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
            \Laminas\HttpHandlerRunner\ConfigProvider::class,
            \Mezzio\ConfigProvider::class,
            \Mezzio\Router\ConfigProvider::class,
            \Laminas\Diactoros\ConfigProvider::class,
            \Mezzio\ProblemDetails\ConfigProvider::class,
            ConfigProvider::class,
        ])->getMergedConfig();

        $dependencies                       = $config['dependencies'];
        $dependencies['services']['config'] = $config;

        $container = new ServiceManager($dependencies);
        $app       = $container->get(Application::class);

        $app->pipe(ErrorHandler::class);
        $app->pipe(ProblemDetailsMiddleware::class);
        $app->pipe(MappingErrorProblemDetailsMiddleware::class);
        $app->pipe(RouteMiddleware::class);
        $app->pipe(BodyParamsMiddleware::class);
        $app->pipe(ImplicitHeadMiddleware::class);
        $app->pipe(ImplicitOptionsMiddleware::class);
        $app->pipe(MethodNotAllowedMiddleware::class);
        $app->pipe(DispatchMiddleware::class);
        $app->pipe(NotFoundHandler::class);

        return [$app, $container->get(TreeMapper::class)];
    }
}

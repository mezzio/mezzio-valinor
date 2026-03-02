<?php

declare(strict_types=1);

namespace MezzioTest\Valinor\Integration;

use CuyZ\Valinor\Mapper\TreeMapper;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Valinor\ConfigProvider;
use MezzioTest\Valinor\Integration\Asset\AddToCart;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function fopen;

#[CoversNothing]
final class MezzioSkeletonIntegrationTest extends TestCase
{
    public function test_runs_a_real_request_cycle_through_mezzio(): void
    {
        $app = $this->makeMezzio();

        $bodyStream = fopen('php://memory', 'rw+');

        self::assertIsResource($bodyStream);

        $request = new ServerRequest(
            [],
            [],
            'https://example.com/cart/ABC123',
            'POST',
            $bodyStream,
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );

        $request->getBody()->write('quantity=999');

        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
{
  "reached_endpoint": true,
  "sku": "ABC123",
  "quantity": 999
}
JSON,
            $app
                ->handle($request)
                ->getBody()
                ->__toString(),
        );
    }

    private function makeMezzio(): Application
    {
        /** @var array{dependencies: array{}} $config */
        $config = new ConfigAggregator([
            \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
            \Laminas\HttpHandlerRunner\ConfigProvider::class,
            \Mezzio\ConfigProvider::class,
            \Mezzio\Router\ConfigProvider::class,
            \Laminas\Diactoros\ConfigProvider::class,
            ConfigProvider::class,
        ])->getMergedConfig();

        $dependencies                       = $config['dependencies'];
        $dependencies['services']['config'] = $config;

        $container = new ServiceManager($dependencies);
        $app       = $container->get(Application::class);
        $mapper    = $container->get(TreeMapper::class);

        $app->pipe(ErrorHandler::class);
        $app->pipe(RouteMiddleware::class);
        $app->pipe(BodyParamsMiddleware::class);
        $app->pipe(ImplicitHeadMiddleware::class);
        $app->pipe(ImplicitOptionsMiddleware::class);
        $app->pipe(MethodNotAllowedMiddleware::class);
        $app->pipe(DispatchMiddleware::class);
        $app->pipe(NotFoundHandler::class);

        $app->post('/cart/{sku}', function (ServerRequestInterface $request) use ($mapper): ResponseInterface {
            $input = $mapper->map(AddToCart::class, $request);

            return new JsonResponse([
                'reached_endpoint' => true,
                'sku'              => $input->sku,
                'quantity'         => $input->quantity,
            ]);
        });

        return $app;
    }
}

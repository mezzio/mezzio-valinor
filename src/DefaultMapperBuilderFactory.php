<?php

declare(strict_types=1);

namespace Mezzio\Valinor;

use CuyZ\Valinor\Mapper\Http\HttpRequest;
use CuyZ\Valinor\MapperBuilder;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

use function assert;

/**
 * @internal you shouldn't reference this class directly: either you rely on the {@see MapperBuilder} service provided
 *           by this package, or you should ship your own service definition.
 *
 * The produced {@see MapperBuilder} service is intentionally built as intermediate step, to allow further DIC steps,
 * such as delegator factories / decorators, to modify it, before a real {@see \CuyZ\Valinor\Mapper\TreeMapper} is
 * assembled.
 *
 * @psalm-internal \MezzioTest
 * @psalm-internal \Mezzio
 */
final class DefaultMapperBuilderFactory
{
    public function __invoke(): MapperBuilder
    {
        return new MapperBuilder()
            ->registerConverter(self::convertServerRequestToNext(...))
            ->allowScalarValueCasting()
            ->allowSuperfluousKeys();
    }

    /**
     * @template T
     * @param pure-callable(HttpRequest): T $next
     * @return T
     * @pure
     */
    private static function convertServerRequestToNext(ServerRequestInterface $request, callable $next): mixed
    {
        /** @psalm-suppress ImpureMethodCall inherently safe call on PSR-7 API */
        $routeResult = $request->getAttribute(RouteResult::class);

        assert($routeResult instanceof RouteResult || $routeResult === null);

        /** @psalm-suppress ImpureMethodCall ::fromPsr is a pure method */
        return $next(HttpRequest::fromPsr($request, $routeResult?->getMatchedParams() ?? []));
    }
}

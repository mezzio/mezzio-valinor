<?php

declare(strict_types=1);

namespace Mezzio\Valinor;

use CuyZ\Valinor\MapperBuilder;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function assert;
use function is_array;

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
            ->allowScalarValueCasting();
    }

    /**
     * @template T
     * @param pure-callable(array): T $next
     * @return T
     * @pure
     */
    private static function convertServerRequestToNext(ServerRequestInterface $request, callable $next): mixed
    {
        /** @psalm-suppress ImpureMethodCall inherently safe call on PSR-7 API */
        $body = $request->getParsedBody();

        if (! is_array($body)) {
            // @TODO do we want mapping to fail, in this case? Let's figure it out in tests
            return $next([]);
        }

        /** @psalm-suppress ImpureMethodCall inherently safe call on PSR-7 API */
        $routeResult = $request->getAttribute(RouteResult::class);

        assert($routeResult === null || $routeResult instanceof RouteResult);

        $routeParameters = $routeResult instanceof RouteResult
            ? $routeResult->getMatchedParams()
            : [];

        return $next(array_merge(
            $body,
            $routeParameters,
        ));
    }
}

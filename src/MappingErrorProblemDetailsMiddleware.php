<?php

declare(strict_types=1);

namespace Mezzio\Valinor;

use CuyZ\Valinor\Mapper\Http\HttpRequestProblemDetails;
use CuyZ\Valinor\Mapper\MappingError;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Exception\RuntimeException;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MappingErrorProblemDetailsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (MappingError $error) {
            throw new class ($error) extends RuntimeException implements ProblemDetailsExceptionInterface {
                use CommonProblemDetailsExceptionTrait;

                private HttpRequestProblemDetails $problemDetails;

                public function __construct(MappingError $mappingError)
                {
                    $this->problemDetails = HttpRequestProblemDetails::fromMappingError($mappingError);

                    parent::__construct(
                        'HTTP request is invalid',
                        StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
                    );
                }

                public function getStatus(): int
                {
                    return $this->problemDetails->status;
                }

                public function getType(): string
                {
                    return $this->problemDetails->type;
                }

                public function getTitle(): string
                {
                    return $this->problemDetails->title;
                }

                public function getDetail(): string
                {
                    return $this->problemDetails->detail;
                }

                public function getAdditionalData(): array
                {
                    return [
                        'errors' => $this->problemDetails->errors,
                    ];
                }
            };
        }
    }
}

<?php

declare(strict_types=1);

namespace Mezzio\Valinor;

use CuyZ\Valinor\Mapper\Http\HttpRequestProblemDetails;
use CuyZ\Valinor\Mapper\MappingError;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Exception\RuntimeException;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MappingErrorProblemDetailsMiddleware implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (MappingError $error) {
            throw new class ($error) extends RuntimeException implements ProblemDetailsExceptionInterface {
                use CommonProblemDetailsExceptionTrait;

                public function __construct(MappingError $mappingError)
                {
                    $problemDetails   = HttpRequestProblemDetails::fromMappingError($mappingError);
                    $this->title      = $problemDetails->title;
                    $this->type       = $problemDetails->type;
                    $this->status     = $problemDetails->status;
                    $this->detail     = $problemDetails->detail;
                    $this->additional = ['errors' => $problemDetails->errors];

                    parent::__construct(
                        'HTTP request is invalid',
                        StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
                        $mappingError,
                    );
                }
            };
        }
    }
}

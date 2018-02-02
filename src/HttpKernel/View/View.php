<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\View;

use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * Value holder, to be handled by ViewHandler and serialized.
 */
final class View
{
    public $result;
    public $headers;
    public $statusCode;
    public $serializationGroups;
    public $serializationType;

    public function __construct($result, int $statusCode = Response::HTTP_OK)
    {
        $this->statusCode = $statusCode;
        $this->headers = [];

        if ($result instanceof ObjectIterator) {
            $this->headers['X-Total-Count'] = $result->count();
        }

        if ($result instanceof PagerIterator) {
            $this->headers['X-Continuation-Token'] = (string) $result->getNextPageToken();
        }

        if ($result instanceof Form) {
            if (! $result->isSubmitted()) {
                $result->submit(null);
            }

            if (! $result->isValid()) {
                $this->statusCode = Response::HTTP_BAD_REQUEST;
            }
        } elseif ($result instanceof \Iterator) {
            $result = iterator_to_array($result);
        }

        $this->result = $result;
    }
}

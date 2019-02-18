<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\View;

use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * Value holder, to be handled by ViewHandler and serialized.
 */
final class View
{
    /**
     * @var mixed
     */
    public $result;

    /**
     * @var array
     */
    public $headers;

    /**
     * @var int
     */
    public $statusCode;

    /**
     * @var string[]|null
     */
    public $serializationGroups;

    /**
     * @var string|null
     */
    public $serializationType;

    public function __construct($result, int $statusCode = Response::HTTP_OK)
    {
        $this->statusCode = $statusCode;
        $this->headers = [];
        $this->result = $result;

        if ($result instanceof Form) {
            if (! $result->isSubmitted()) {
                $result->submit(null);
            }

            if (! $result->isValid()) {
                $this->statusCode = Response::HTTP_BAD_REQUEST;
            }

            return;
        }

        if ($result instanceof \IteratorAggregate) {
            $result = $result->getIterator();
        }

        if ($result instanceof ObjectIteratorInterface) {
            $this->headers['X-Total-Count'] = $result->count();
        }

        if ($result instanceof PagerIterator) {
            $this->headers['X-Continuation-Token'] = (string) $result->getNextPageToken();
        }

        if ($result instanceof \Iterator) {
            $this->result = \iterator_to_array($result);
        }
    }
}

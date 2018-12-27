<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Exception;

class SyntaxError extends \RuntimeException implements ExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string          $buffer
     * @param int             $position
     * @param \Throwable|null $previous
     */
    public function __construct(string $buffer = '', int $position = 0, ?\Throwable $previous = null)
    {
        $start = \max($position - 7, 0);
        $end = \min($position + 20, \strlen($buffer));
        $excerpt = \substr($buffer, $start, $end - $start);

        parent::__construct('Syntax Error: could not parse "'.\trim($excerpt).'"', 0, $previous);
    }
}

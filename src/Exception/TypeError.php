<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Exception;

class TypeError extends \TypeError
{
    /**
     * Creates and format an argument invalid error.
     *
     * @param int             $no       Argument number
     * @param string          $function Function generating the error
     * @param string|string[] $expected Expected type(s)
     * @param mixed           $given    Given value
     *
     * @return self
     */
    public static function createArgumentInvalid(int $no, string $function, $expected, $given): self
    {
        $message = \sprintf(
            'Argument %u passed to %s must be of type %s, %s given',
            $no, $function,
            self::formatExpected($expected),
            \is_object($given) ? \get_class($given) : \gettype($given)
        );

        return new self($message);
    }

    /**
     * Formats "expected" value for exception message.
     *
     * @param string|string[] $expected
     *
     * @return string
     */
    private static function formatExpected($expected): string
    {
        if (! \is_array($expected)) {
            return $expected;
        }

        $last = \array_pop($expected);

        return \implode(', ', $expected).' or '.$last;
    }
}

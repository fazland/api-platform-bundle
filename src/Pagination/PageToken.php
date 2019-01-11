<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination;

use Fazland\ApiPlatformBundle\Pagination\Exception\InvalidArgumentException;
use Fazland\ApiPlatformBundle\Pagination\Exception\InvalidTokenException;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class is the object representation of a ContinuousToken.
 * The three fields are each:.
 *
 * - the timestamp representation for an object (default: an unix timestamp), this will be calculated from a getPageableTimestamp function of a PageableInterface object
 * - the offset of the object relative to similar objects with the same timestamp (eg. 3 object with same timestamp, the second one will be represented by a "2" offset)
 * - the checksum which will work as following:
 *
 *   "given a request, i'll get the last timestamp which will be used as a reference. Then i'll work backwards collecting every entity with the same timestamp.
 *    I'll get the ids of each of these entities with the same timestamp, and put them into an array. The array will be imploded into a string which will be subjected
 *    to a crc32 function"
 *
 *   The checksum is there to control cases in which entities are modified into the database. The pager will control the checksum, and in case of a conflict (the database
 *   has changed), it will notify it to the client (mostly the fallback procedure is to return the first page of the entities).
 */
final class PageToken
{
    const TOKEN_DELIMITER = '_';

    /**
     * The timestamp used as starting point to "cut" the object set.
     *
     * @var \DateTimeInterface
     */
    private $orderValue;

    /**
     * How many elements should be skipped from the filtered set.
     *
     * @var int
     */
    private $offset;

    /**
     * The checksum if the first $offset elements.
     *
     * @var string
     */
    private $checksum;

    public function __construct($orderValue, int $offset, int $checksum)
    {
        if ($offset < 1) {
            throw new InvalidArgumentException('Offset cannot be less than 1');
        }

        $this->orderValue = $orderValue;
        $this->offset = $offset;
        $this->checksum = $checksum;
    }

    public function __toString(): string
    {
        /*
         * Example token, with "_" Delimiter: 1262338074_1_3632233996
         * - the first part is a standard unix timestamp ('U' format)
         * - the second part is an offset indicating the position among "same-timestamp"
         * entities(eg. if the last element of the page is second within 3 elements with the same timestamp, the value will be 2)
         * - the third part represents the checksum as crc32($entitiesWithSameTimestampInThisPage->getIds());
         */

        if (\is_numeric($this->orderValue)) {
            $timestamp = \base_convert($this->orderValue, 10, 36);

            return \implode(self::TOKEN_DELIMITER, [
                $timestamp,
                $this->offset,
                \base_convert($this->checksum, 10, 36),
            ]);
        }

        return \implode(self::TOKEN_DELIMITER, [
            '='.\base64_encode($this->orderValue),
            $this->offset,
            \base_convert($this->checksum, 10, 36),
        ]);
    }

    /**
     * Parses a token and returns a valid PageToken object.
     * Throws InvalidTokenException if $token is invalid.
     *
     * @param string $token
     *
     * @return static
     *
     * @throws InvalidTokenException
     */
    public static function parse(string $token): self
    {
        $tokenSplit = \explode(self::TOKEN_DELIMITER, $token);
        if (3 !== \count($tokenSplit)) {
            throw new InvalidTokenException('Malformed token');
        }

        list($orderValue, $offset, $checksum) = $tokenSplit;

        if ('=' === $orderValue[0]) {
            $orderValue = \base64_decode(\substr($orderValue, 1));
        } else {
            $orderValue = (int) \base_convert($tokenSplit[0], 36, 10);
        }

        return new self(
            $orderValue,
            (int) $offset,
            (int) \base_convert($checksum, 36, 10)
        );
    }

    /**
     * Extract the token from the request and parses it.
     *
     * @param Request $request
     *
     * @return self|null
     */
    public static function fromRequest(Request $request): ?self
    {
        if (empty($token = $request->query->get('continue'))) {
            return null;
        }

        return self::parse($token);
    }

    /**
     * Gets the page timestamp (starting point).
     *
     * @return mixed
     */
    public function getOrderValue()
    {
        return $this->orderValue;
    }

    /**
     * Gets the filtered set offset.
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Gets the checksum of the first-$offset elements.
     *
     * @return int
     */
    public function getChecksum(): int
    {
        return $this->checksum;
    }
}

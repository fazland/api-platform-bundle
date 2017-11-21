<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Negotiation;

use Negotiation\Accept;
use Negotiation\AcceptHeader;
use Negotiation\BaseAccept;
use Negotiation\Exception\InvalidArgument;
use Negotiation\Exception\InvalidHeader;
use Negotiation\Match;

class VersionAwareNegotiator
{
    protected function acceptFactory($header)
    {
        return new Accept($header);
    }

    /**
     * Build Accept object for priority.
     *
     * @param string $priority
     *
     * @return BaseAccept
     */
    public function priorityFactory($priority): BaseAccept
    {
        $priority = new Priority($priority);

        $params = $priority->getParameters();
        if (isset($params['version'])) {
            throw new InvalidHeader("Priority cannot have a 'version' parameter");
        }

        return $priority;
    }

    /**
     * @param string $header     a string containing an `Accept|Accept-*` header
     * @param array  $priorities a set of server priorities
     *
     * @return Priority|null best matching type
     */
    public function getBest($header, array $priorities): ?Priority
    {
        if (empty($priorities)) {
            throw new InvalidArgument('A set of server priorities should be given.');
        }

        if (! $header) {
            throw new InvalidArgument('The header string should not be empty.');
        }

        $headers = $this->parseHeader($header);
        $headers = array_map([$this, 'acceptFactory'], $headers);
        $priorities = array_map([$this, 'priorityFactory'], $priorities);

        $matches = $this->findMatches($headers, $priorities);
        $specificMatches = array_reduce($matches, 'Negotiation\Match::reduce', []);

        usort($specificMatches, 'Negotiation\Match::compare');

        $match = array_shift($specificMatches);

        if (null === $match) {
            return null;
        }

        /** @var Priority $priority */
        $priority = $priorities[$match->index];
        $priority->setVersion($headers[$match->headerIndex]->getParameter('version'));

        return $priority;
    }

    protected function match(AcceptHeader $accept, AcceptHeader $priority, $index, $headerIndex)
    {
        if (! $accept instanceof Accept || ! $priority instanceof Priority) {
            return null;
        }

        $ab = $accept->getBasePart();
        $pb = $priority->getBasePart();

        $as = $accept->getSubPart();
        $ps = $priority->getSubPart();

        $accept_params = $accept->getParameters();
        unset($accept_params['version']);

        $intersection = array_intersect_assoc($accept_params, $priority->getParameters());

        $baseEqual = ! strcasecmp($ab, $pb);
        $subEqual = ! strcasecmp($as, $ps);

        if (('*' === $ab || $baseEqual) && ('*' === $as || $subEqual) && count($intersection) === count($accept_params)) {
            $score = 100 * $baseEqual + 10 * $subEqual + count($intersection);

            $match = new Match($accept->getQuality(), $score, $index);
            $match->headerIndex = $headerIndex;

            return $match;
        }

        return null;
    }

    /**
     * @param string $header a string that contains an `Accept*` header
     *
     * @return string[]
     */
    private function parseHeader($header): array
    {
        $res = preg_match_all('/(?:[^,"]*+(?:"[^"]*+")?)+[^,"]*+/', $header, $matches);

        if (! $res) {
            throw new InvalidHeader(sprintf('Failed to parse accept header: "%s"', $header));
        }

        return array_values(array_filter(array_map('trim', $matches[0])));
    }

    /**
     * @param AcceptHeader[] $headerParts
     * @param AcceptHeader[] $priorities  Configured priorities
     *
     * @return Match[] Headers matched
     */
    private function findMatches(array $headerParts, array $priorities): array
    {
        $matches = [];
        foreach ($priorities as $index => $p) {
            foreach ($headerParts as $hIndex => $h) {
                if (null !== $match = $this->match($h, $p, $index, $hIndex)) {
                    $matches[] = $match;
                }
            }
        }

        return $matches;
    }
}

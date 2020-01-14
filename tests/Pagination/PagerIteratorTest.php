<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Pagination;

use Cake\Chronos\Chronos;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PagerIteratorTest extends TestCase
{
    private const PAGE_SIZE = 3;
    private const CASE_1 = [1, 2, 3, 4, 5, 6];
    private const CASE_2 = [1, 2, 2, 2, 2, 3];
    private const VALID_UUIDS = [
        'b4902bde-28d2-4ff9-8971-8bfeb3e943c1',
        '191a54d8-990c-4ea7-9a23-0aed29d1fffe',
        '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
        'af6394a4-7344-4fe8-9748-e6c67eba5ade',
        '84810e2e-448f-4f58-acb8-4db1381f5de3',
        'eadd7470-95f5-47e8-8e74-083d45c307f6',
    ];
    private const WRONG_UUIDS = [
        'eadd7470-95f5-47e8-8e74-083d45c307f6',
        '84810e2e-448f-4f58-acb8-4db1381f5de3',
        'af6394a4-7344-4fe8-9748-e6c67eba5ade',
        '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
        '191a54d8-990c-4ea7-9a23-0aed29d1fffe',
        'b4902bde-28d2-4ff9-8971-8bfeb3e943c1',
    ];

    protected function generatePageableInterfaceListFromArray(array $template, array $uuid_list, $modify = '+1 hours'): array
    {
        $previous = null;
        $previousTimestamp = null;
        $result = [];

        foreach ($template as $key => $pointer) {
            if ($previous === $pointer && null !== $previous && null !== $previousTimestamp) {
                $timestamp = $previousTimestamp;
            } elseif (null !== $previousTimestamp) {
                $timestamp = Chronos::instance($previousTimestamp)->modify($modify);
            } else {
                $timestamp = new Chronos('1991-11-24 00:00:00');
            }

            $object = new TestObject($uuid_list[$key], $timestamp);

            $result[] = $object;

            $previousTimestamp = $timestamp;
            $previous = $pointer;
        }

        return $result;
    }

    public function testPagerShouldGenerateFirstPageWithToken(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_1, self::VALID_UUIDS), ['timestamp' => 'ASC', 'id' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('b4902bde-28d2-4ff9-8971-8bfeb3e943c1', new Chronos('1991-11-24 00:00:00')),
                new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 01:00:00')),
                new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 02:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(
            'bfdew0_1_1jvdwz4', (string) $pager->getNextPageToken()
        );
    }

    public function testPagerShouldGenerateFirstPageWithStringToken(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_1, self::VALID_UUIDS), ['id' => 'ASC', 'timestamp' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 01:00:00')),
                new TestObject('84810e2e-448f-4f58-acb8-4db1381f5de3', new Chronos('1991-11-24 04:00:00')),
                new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 02:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(
            '=OWM1ZjZmZjctYjI4Zi00OGZiLWJhNDctOGJjYzNiMjM1YmVk_1_68lkk0', (string) $pager->getNextPageToken()
        );
    }

    public function testPagerShouldGenerateSecondPageWithTokenAndLastPage(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_1, self::VALID_UUIDS), ['timestamp' => 'ASC', 'id' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdew0_1_1jvdwz4']);

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 03:00:00')),
                new TestObject('84810e2e-448f-4f58-acb8-4db1381f5de3', new Chronos('1991-11-24 04:00:00')),
                new TestObject('eadd7470-95f5-47e8-8e74-083d45c307f6', new Chronos('1991-11-24 05:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(
            'bfdn80_1_cukvcs', (string) $pager->getNextPageToken()
        );
    }

    public function testPagerShouldGenerateSecondPageWithStringTokenAndLastPage(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_1, self::VALID_UUIDS), ['id' => 'ASC', 'timestamp' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => '=OWM1ZjZmZjctYjI4Zi00OGZiLWJhNDctOGJjYzNiMjM1YmVk_1_68lkk0']);

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 03:00:00')),
                new TestObject('b4902bde-28d2-4ff9-8971-8bfeb3e943c1', new Chronos('1991-11-24 00:00:00')),
                new TestObject('eadd7470-95f5-47e8-8e74-083d45c307f6', new Chronos('1991-11-24 05:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(
            '=ZWFkZDc0NzAtOTVmNS00N2U4LThlNzQtMDgzZDQ1YzMwN2Y2_1_aa6ezc', (string) $pager->getNextPageToken()
        );
    }

    public function testOffsetShouldWork(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_2, self::VALID_UUIDS), ['timestamp' => 'ASC', 'id' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('b4902bde-28d2-4ff9-8971-8bfeb3e943c1', new Chronos('1991-11-24 00:00:00')),
                new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 01:00:00')),
                new TestObject('84810e2e-448f-4f58-acb8-4db1381f5de3', new Chronos('1991-11-24 01:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(2, $pager->getNextPageToken()->getOffset());

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdc40_2_hzr9o9']);

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 01:00:00')),
                new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 01:00:00')),
                new TestObject('eadd7470-95f5-47e8-8e74-083d45c307f6', new Chronos('1991-11-24 02:00:00')),
            ],
            \iterator_to_array($pager)
        );
    }

    public function testPagerShouldReturnFirstPageWithTimestampDifference(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_1, self::VALID_UUIDS, '+2 hours'), ['timestamp' => 'ASC', 'id' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdew0_1_1jvdwz4']); // This token represents a request with the 02:00:00 timestamp

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 02:00:00')),
                new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 04:00:00')),
                new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 06:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(
            'bfdq00_1_1dv9eb9', (string) $pager->getNextPageToken()
        );
    }

    public function testPagerShouldReturnFirstPageWithChecksumDifference(): void
    {
        $pager = new PagerIterator(
            $this->generatePageableInterfaceListFromArray(self::CASE_1, self::WRONG_UUIDS), ['timestamp' => 'ASC', 'id' => 'ASC']
        );
        $pager->setPageSize(self::PAGE_SIZE);

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdew0_1_1jvdwz4']); // This token represents a request with the 02:00:00 timestamp

        $pager->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals(
            [
                new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 02:00:00')),
                new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 03:00:00')),
                new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 04:00:00')),
            ],
            \iterator_to_array($pager)
        );

        self::assertEquals(
            'bfdkg0_1_7gqxdp', (string) $pager->getNextPageToken()
        );
    }
}

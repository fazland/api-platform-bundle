<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Negotiation;

use Fazland\ApiPlatformBundle\Negotiation\Priority;
use Fazland\ApiPlatformBundle\Negotiation\VersionAwareNegotiator;
use Negotiation\Exception\InvalidArgument;
use Negotiation\Exception\InvalidHeader;
use Negotiation\Exception\InvalidMediaType;
use PHPUnit\Framework\TestCase;

class VersionAwareNegotiatorTest extends TestCase
{
    /**
     * @var VersionAwareNegotiator
     */
    private $negotiator;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->negotiator = new VersionAwareNegotiator();
    }

    public function testPriorityFactoryRaiseExceptionIfContainsVersionParam(): void
    {
        $this->expectException(InvalidHeader::class);
        $this->negotiator->priorityFactory('text/html; version=12');
    }

    public function testPriorityFactoryRaiseExceptionIfInvalidMediaType(): void
    {
        $this->expectException(InvalidMediaType::class);
        $this->negotiator->priorityFactory('html');
    }

    /**
     * @dataProvider dataProviderForTestGetBest
     */
    public function testGetBest(string $header, array $priorities, $expected): void
    {
        try {
            $acceptHeader = $this->negotiator->getBest($header, $priorities);
        } catch (\Exception $e) {
            self::assertEquals($expected, $e);

            return;
        }

        if (null === $acceptHeader) {
            self::assertNull($expected);

            return;
        }

        self::assertInstanceOf(Priority::class, $acceptHeader);

        self::assertSame($expected[0], $acceptHeader->getType());
        self::assertSame($expected[1], $acceptHeader->getParameters());
    }

    public static function dataProviderForTestGetBest(): iterable
    {
        $pearAcceptHeader = 'text/html,application/xhtml+xml,application/xml;q=0.9,text/*;q=0.7,*/*,image/gif; q=0.8, image/jpeg; q=0.6, image/*';
        $rfcHeader = 'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5';

        return [
            // exceptions
            ['/qwer', ['f/g'], new InvalidMediaType()],
            ['', ['foo/bar'], new InvalidArgument('The header string should not be empty.')],
            ['*/*', [], new InvalidArgument('A set of server priorities should be given.')],
            [',', ['text/html'], new InvalidHeader('Failed to parse accept header: ","')],

            // See: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
            [$rfcHeader, ['text/html;level=1'], ['text/html', ['level' => '1']]],
            [$rfcHeader, ['text/html'], ['text/html', []]],
            [$rfcHeader, ['text/plain'], ['text/plain', []]],
            [$rfcHeader, ['image/jpeg'], ['image/jpeg', []]],
            [$rfcHeader, ['text/html;level=2'], ['text/html', ['level' => '2']]],
            [$rfcHeader, ['text/html;level=3'], ['text/html', ['level' => '3']]],

            ['text/*;q=0.7, text/html;q=0.3, */*;q=0.5, image/png;q=0.4', ['text/html', 'image/png'], ['image/png', []]],
            ['image/png;q=0.1, text/plain, audio/ogg;q=0.9', ['image/png', 'text/plain', 'audio/ogg'], ['text/plain', []]],
            ['image/png, text/plain, audio/ogg', ['baz/asdf'], null],
            ['image/png, text/plain, audio/ogg', ['audio/ogg'], ['audio/ogg', []]],
            ['image/png, text/plain, audio/ogg', ['YO/SuP'], null],
            ['text/html; charset=UTF-8, application/pdf', ['text/html; charset=UTF-8'], ['text/html', ['charset' => 'UTF-8']]],
            ['text/html; charset=UTF-8, application/pdf', ['text/html'], null],
            ['text/html, application/pdf', ['text/html; charset=UTF-8'], ['text/html', ['charset' => 'UTF-8']]],
            // PEAR HTTP2 tests - have been altered from original!
            [$pearAcceptHeader, ['image/gif', 'image/png', 'application/xhtml+xml', 'application/xml', 'text/html', 'image/jpeg', 'text/plain'], ['image/png', []]],
            [$pearAcceptHeader, ['image/gif', 'application/xhtml+xml', 'application/xml', 'image/jpeg', 'text/plain'], ['application/xhtml+xml', []]],
            [$pearAcceptHeader, ['image/gif', 'application/xml', 'image/jpeg', 'text/plain'], ['application/xml', []]],
            [$pearAcceptHeader, ['image/gif', 'image/jpeg', 'text/plain'], ['image/gif', []]],
            [$pearAcceptHeader, ['text/plain', 'image/png', 'image/jpeg'], ['image/png', []]],
            [$pearAcceptHeader, ['image/jpeg', 'image/gif'], ['image/gif', []]],
            [$pearAcceptHeader, ['image/png'], ['image/png', []]],
            [$pearAcceptHeader, ['audio/midi'], ['audio/midi', []]],
            ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', ['application/rss+xml'], ['application/rss+xml', []]],
            // LWS / case sensitivity
            ['text/* ; q=0.3, TEXT/html ;Q=0.7, text/html ; level=1, texT/Html ;leVel = 2 ;q=0.4, */* ; q=0.5', ['text/html; level=2'], ['text/html', ['level' => '2']]],
            ['text/* ; q=0.3, text/html;Q=0.7, text/html ;level=1, text/html; level=2;q=0.4, */*;q=0.5', ['text/HTML; level=3'], ['text/html', ['level' => '3']]],
            // Incompatible
            ['text/html', ['application/rss'], null],
            // IE8 Accept header
            ['image/jpeg, application/x-ms-application, image/gif, application/xaml+xml, image/pjpeg, application/x-ms-xbap, */*', ['text/html', 'application/xhtml+xml'], ['text/html', []]],
        ];
    }
}

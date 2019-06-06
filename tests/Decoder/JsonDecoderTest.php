<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Decoder;

use Fazland\ApiPlatformBundle\Decoder\JsonDecoder;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Decoder\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class JsonDecoderTest extends WebTestCase
{
    /**
     * @var JsonDecoder
     */
    private $decoder;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->decoder = new JsonDecoder();
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function dataProviderForDecode(): iterable
    {
        return [
            [[], ''],
            [['option' => 0], '{ "option": false }'],
            [['option' => 1], '{ "option": true }'],
            [['options' => ['option' => 0]], '{ "options": { "option": false } }'],
            [['options' => ['option' => 'foobar']], '{ "options": { "option": "foobar" } }'],
        ];
    }

    /**
     * @dataProvider dataProviderForDecode
     */
    public function testDecode(array $expected, string $input): void
    {
        self::assertEquals($expected, $this->decoder->decode($input));
    }

    public function testShouldDecodeContentCorrectly(): void
    {
        $client = static::createClient();

        $client->request('POST', '/', [], [], ['CONTENT_TYPE' => 'application/json'], '{ "options": { "option": false } }');
        $response = $client->getResponse();

        $array = <<<EOF
array:1 [
  "options" => array:1 [
    "option" => "0"
  ]
]
EOF;

        self::assertEquals($array, $response->getContent());
    }
}

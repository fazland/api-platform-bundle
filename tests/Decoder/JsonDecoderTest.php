<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Decoder;

use Fazland\ApiPlatformBundle\Decoder\JsonDecoder;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Decoder\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JsonDecoderTest extends WebTestCase
{
    /**
     * @var JsonDecoder
     */
    private $decoder;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    public function setUp()
    {
        $this->decoder = new JsonDecoder();
    }

    public function dataProviderForDecode()
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
    public function testDecode(array $expected, string $input)
    {
        $this->assertEquals($expected, $this->decoder->decode($input));
    }

    public function testShouldDecodeContentCorrectly()
    {
        $client = static::createClient();

        $client->request('POST', '/', [], [], ['CONTENT_TYPE' => 'application/json'], '{ "options": { "option": false } }');
        $response = $client->getResponse();

        $array = <<<EOF
array:1 [
  "options" => array:1 [
    "option" => null
  ]
]
EOF;

        $this->assertEquals($array, $response->getContent());
    }
}

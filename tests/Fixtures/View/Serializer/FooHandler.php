<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\View\Serializer;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class FooHandler implements SubscribingHandlerInterface
{
    public function getSubscribingMethods()
    {
        yield [
            'type' => 'FooObject',
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serialize',
        ];
    }

    public function serialize(VisitorInterface $visitor, array $data, Type $type, Context $context)
    {
        $data['additional'] = 'foo';

        return $visitor->visitArray($data, $type, $context);
    }
}

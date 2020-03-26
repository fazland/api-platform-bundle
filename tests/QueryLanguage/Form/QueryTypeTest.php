<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Form;

use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\QueryType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class QueryTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    protected function getValidatorExtension(): ValidatorExtension
    {
        return new ValidatorExtension(Validation::createValidator());
    }

    public function testShouldNotAllowNegativeSkip(): void
    {
        $form = $this->factory->createNamed('', QueryType::class, $query = new Query(), [
            'columns' => [],
            'skip_field' => 'skip',
        ]);
        $form->submit(['skip' => -10]);

        self::assertFalse($form->isValid());
    }

    public function testShouldNotAllowNegativeLimit(): void
    {
        $form = $this->factory->createNamed('', QueryType::class, $query = new Query(), [
            'columns' => [],
            'limit_field' => 'limit',
        ]);
        $form->submit(['limit' => -10]);

        self::assertFalse($form->isValid());
    }

    public function testShouldAcceptSkipAndLimit(): void
    {
        $form = $this->factory->createNamed('', QueryType::class, $query = new Query(), [
            'columns' => [],
            'skip_field' => 'skip',
            'limit_field' => 'limit',
        ]);
        $form->submit([
            'skip' => 0,
            'limit' => 10,
        ]);

        self::assertTrue($form->isValid());
    }
}

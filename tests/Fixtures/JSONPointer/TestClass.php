<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer;

class TestClass
{
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;

    private $publicAccessor;
    private $publicMethodAccessor;
    private $publicGetSetter;
    private $publicAccessorWithDefaultValue;
    private $publicAccessorWithRequiredAndDefaultValue;
    private $publicAccessorWithMoreRequiredParameters;
    private $publicIsAccessor;
    private $publicHasAccessor;
    private $publicGetter;
    private $date;

    public function __construct($value)
    {
        $this->publicProperty = $value;
        $this->publicAccessor = $value;
        $this->publicMethodAccessor = $value;
        $this->publicGetSetter = $value;
        $this->publicAccessorWithDefaultValue = $value;
        $this->publicAccessorWithRequiredAndDefaultValue = $value;
        $this->publicAccessorWithMoreRequiredParameters = $value;
        $this->publicIsAccessor = $value;
        $this->publicHasAccessor = $value;
        $this->publicGetter = $value;
    }

    public function getPublicAccessor()
    {
        return $this->publicAccessor;
    }

    public function setPublicAccessor($value): void
    {
        $this->publicAccessor = $value;
    }

    public function getPublicAccessorWithDefaultValue()
    {
        return $this->publicAccessorWithDefaultValue;
    }

    public function setPublicAccessorWithDefaultValue($value = null): void
    {
        $this->publicAccessorWithDefaultValue = $value;
    }

    public function getPublicAccessorWithRequiredAndDefaultValue()
    {
        return $this->publicAccessorWithRequiredAndDefaultValue;
    }

    public function setPublicAccessorWithRequiredAndDefaultValue($value, $optional = null): void
    {
        $this->publicAccessorWithRequiredAndDefaultValue = $value;
    }

    public function getPublicAccessorWithMoreRequiredParameters()
    {
        return $this->publicAccessorWithMoreRequiredParameters;
    }

    public function setPublicAccessorWithMoreRequiredParameters($value, $needed): void
    {
        $this->publicAccessorWithMoreRequiredParameters = $value;
    }

    public function isPublicIsAccessor()
    {
        return $this->publicIsAccessor;
    }

    public function setPublicIsAccessor($value): void
    {
        $this->publicIsAccessor = $value;
    }

    public function hasPublicHasAccessor()
    {
        return $this->publicHasAccessor;
    }

    public function setPublicHasAccessor($value): void
    {
        $this->publicHasAccessor = $value;
    }

    public function publicGetSetter($value = null)
    {
        if (null !== $value) {
            $this->publicGetSetter = $value;
        }

        return $this->publicGetSetter;
    }

    public function getPublicMethodMutator()
    {
        return $this->publicGetSetter;
    }

    protected function getProtectedAccessor(): string
    {
        return 'foobar';
    }

    protected function setProtectedAccessor($value): void
    {
    }

    protected function isProtectedIsAccessor(): string
    {
        return 'foobar';
    }

    protected function setProtectedIsAccessor($value): void
    {
    }

    protected function hasProtectedHasAccessor(): string
    {
        return 'foobar';
    }

    protected function setProtectedHasAccessor($value): void
    {
    }

    private function getPrivateAccessor(): string
    {
        return 'foobar';
    }

    private function setPrivateAccessor($value): void
    {
    }

    private function isPrivateIsAccessor(): string
    {
        return 'foobar';
    }

    private function setPrivateIsAccessor($value): void
    {
    }

    private function hasPrivateHasAccessor(): string
    {
        return 'foobar';
    }

    private function setPrivateHasAccessor($value): void
    {
    }

    public function getPublicGetter()
    {
        return $this->publicGetter;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }
}

<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as PhoneNumberObject;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PhoneNumberValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (! $constraint instanceof PhoneNumber) {
            throw new UnexpectedTypeException($value, PhoneNumber::class);
        }

        if (! \is_string($value) && ! $value instanceof PhoneNumberObject) {
            throw new UnexpectedTypeException($value, 'string or '.PhoneNumberObject::class);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        if ($value instanceof PhoneNumberObject) {
            $phoneNumber = $value;
        } else {
            try {
                $phoneNumber = $phoneUtil->parse($value);
            } catch (NumberParseException $e) {
                $this->addViolation($value, $constraint);

                return;
            }
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            $formattedNumber = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            $this->addViolation($formattedNumber, $constraint);

            return;
        }

        switch ($constraint->getType()) {
            case PhoneNumber::FIXED_LINE:
                $validTypes = [PhoneNumberType::FIXED_LINE, PhoneNumberType::FIXED_LINE_OR_MOBILE];
                break;

            case PhoneNumber::MOBILE:
                $validTypes = [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE];
                break;

            case PhoneNumber::PAGER:
                $validTypes = [PhoneNumberType::PAGER];
                break;

            case PhoneNumber::PERSONAL_NUMBER:
                $validTypes = [PhoneNumberType::PERSONAL_NUMBER];
                break;

            case PhoneNumber::PREMIUM_RATE:
                $validTypes = [PhoneNumberType::PREMIUM_RATE];
                break;

            case PhoneNumber::SHARED_COST:
                $validTypes = [PhoneNumberType::SHARED_COST];
                break;

            case PhoneNumber::TOLL_FREE:
                $validTypes = [PhoneNumberType::TOLL_FREE];
                break;

            case PhoneNumber::UAN:
                $validTypes = [PhoneNumberType::UAN];
                break;

            case PhoneNumber::VOIP:
                $validTypes = [PhoneNumberType::VOIP];
                break;

            case PhoneNumber::VOICEMAIL:
                $validTypes = [PhoneNumberType::VOICEMAIL];
                break;

            default:
                $validTypes = [];
                break;
        }

        if (\count($validTypes)) {
            $type = $phoneUtil->getNumberType($phoneNumber);

            if (false === \in_array($type, $validTypes)) {
                $this->addViolation($value, $constraint);

                return;
            }
        }
    }

    /**
     * Adds a violation.
     *
     * @param mixed      $value      the value that should be validated
     * @param Constraint $constraint the constraint for the validation
     */
    private function addViolation($value, Constraint $constraint): void
    {
        $this->context->addViolation(
            $constraint->getMessage(),
            ['{{ type }}' => $constraint->getType(), '{{ value }}' => $value]
        );
    }
}

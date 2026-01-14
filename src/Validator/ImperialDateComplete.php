<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
class ImperialDateComplete extends Constraint
{
    public function __construct(
        public bool $required = false,
        public string $message = 'Log the full Imperial stamp (day and year).',
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

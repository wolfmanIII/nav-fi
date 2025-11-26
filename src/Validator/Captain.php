<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Captain extends Constraint
{
    public string $message =
        'La nave "{{ ship }}" ha già un capitano assegnato: {{ name }}.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

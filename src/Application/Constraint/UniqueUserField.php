<?php

declare(strict_types=1);

namespace App\Application\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class UniqueUserField extends Constraint
{
    public string $message = 'This {{ field }} is already in use.';
}

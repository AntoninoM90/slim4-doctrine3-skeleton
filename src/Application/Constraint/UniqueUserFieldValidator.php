<?php

declare(strict_types=1);

namespace App\Application\Constraint;

use App\Application\Request\User\UpdateUserRequest;
use App\Domain\User\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserFieldValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserField) {
            throw new UnexpectedTypeException($constraint, UniqueUserField::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $field = $this->resolveField();

        if ($field === null) {
            return;
        }

        $existingUser = $this->userRepository->findOneUserBy([$field => $value]);

        if ($existingUser === null) {
            return;
        }

        $object = $this->context->getObject();
        $excludedUserId = $object instanceof UpdateUserRequest
            ? $object->getExcludedUserId()
            : null;

        if ($excludedUserId !== null && $existingUser->getId() === $excludedUserId) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ field }}', $field === 'username' ? 'username' : 'email address')
            ->addViolation();
    }

    private function resolveField(): ?string
    {
        return match ($this->context->getPropertyPath()) {
            'username' => 'username',
            'emailAddress' => 'emailAddress',
            default => null,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use Psr\Container\ContainerInterface;

abstract class UserAction extends Action
{
    protected UserRepository $userRepository;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->userRepository = new UserRepository($this->entityManager);
    }
}

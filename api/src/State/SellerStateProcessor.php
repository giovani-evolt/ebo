<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Seller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;

class SellerStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Seller
    {
        if($operation instanceof \ApiPlatform\Metadata\Post) {
            $user = $this->security->getUser();

            if (!$user instanceof User) {
                throw new AccessDeniedException('Usuario no autenticado');
            }

            $data->addUser($user);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

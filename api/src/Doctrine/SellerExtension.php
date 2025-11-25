<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class SellerExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security
    ) {}

    private function addSellerFilter(QueryBuilder $qb, string $resourceClass): void
    {
        // Si la entidad NO tiene el campo seller, no aplicar nada
        if (!property_exists($resourceClass, 'seller')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user || !$user->getSeller()) {
            throw new \LogicException('El usuario no está autenticado o no tiene un seller asociado.');
        }

        $seller = $user->getSeller()->getId();
        $alias = $qb->getRootAliases()[0];

        $qb
            ->andWhere(sprintf('%s.seller = :seller', $alias))
            ->setParameter('seller', $seller);
    }

    public function applyToCollection(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operationName = null,
        array $context = []
    ): void {
        $this->addSellerFilter($qb, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operationName = null,
        array $context = []
    ): void {
        $this->addSellerFilter($qb, $resourceClass);
    }
}

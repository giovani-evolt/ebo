<?php

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\Amazon\Settlement\TransactionTotalRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TransactionTotalsProvider implements ProviderInterface
{
    public function __construct(
        private TransactionTotalRepository $repository,
        private Security $security
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if($operation instanceof GetCollection) {
            $filters = $context['filters'] ?? [];

            $year = $filters['year'] ?? null;
            $month = $filters['month'] ?? null;
            $seller = $this->security->getUser()->getSeller();  

            $result = $this->repository->getSummaryByYearMonth(
                $year,
                $month,
                $seller
            );

            return $result;
        }
        
        return [];
    }
}

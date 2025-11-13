<?php

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\Amazon\Settlement\TransactionTotalRepository;


class TransactionTotalsProvider implements ProviderInterface
{

    protected $transactionTotalRepository;

    public function __construct(
        private TransactionTotalRepository $repository
    ){
        $this->transactionTotalRepository = $repository;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if($operation instanceof GetCollection) {
            $filters = $context['filters'] ?? [];

            $year = $filters['year'] ?? null;
            $month = $filters['month'] ?? null;

            $result = $this->transactionTotalRepository->getSummaryByYearMonth(
                $year,
                $month
            );

            return $result;
        }
        
        return [];
    }
}

<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Seller\Csv;
use App\Message\SendNewCSVMessage;
use App\Service\IngestService;
use Symfony\Component\Messenger\MessageBusInterface;

class CsvStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private MEssageBusInterface $messageBus,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if($operation instanceof Post){
            $data->setType(1000);
            $data->setStatus(Csv::STATUS_PENDING);

            // $this->messageBus->dispatch(New SendNewCSVMessage(
            //     sellerCode: $data->getSeller()->getCode(),
            //     csvCode: $data->getCode()->toString(),
            // ));
        }

        $csv = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $csv;
    }
}

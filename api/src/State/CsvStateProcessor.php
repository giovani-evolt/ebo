<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Seller\Csv;
use App\Message\SendNewCSVMessage;
use App\Service\IngestService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CsvStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private MessageBusInterface $messageBus,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if($operation instanceof Post){
            $data->setType(1000);
            $data->setStatus(Csv::STATUS_PENDING);
            $data->setSeller($this->security->getUser()->getSeller());
            // $this->messageBus->dispatch(New SendNewCSVMessage(
            //     sellerCode: $data->getSeller()->getCode(),
            //     csvCode: $data->getCode()->toString(),
            // ));
        }elseif ($operation instanceof Delete) {
            if($data->getStatus() !== Csv::STATUS_PENDING){
                throw new \Exception('No se puede eliminar un CSV que no ha sido procesado completamente.');
            }
        }

        $csv = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $csv;
    }
}

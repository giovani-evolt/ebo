<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

class MerchantStateProcesor implements ProcessorInterface
{
    const CODE_LENGTH = 5;

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if($operation instanceof \ApiPlatform\Metadata\Post) {
            $data->setCode($this->getMerchantCode($data->getName()));
        }

        return $data;
    }

    private function getMerchantCode(string $input): string {
        // Filtrar solo las consonantes
        $consonants = preg_replace('/[^bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]/', '', $input);

        // Si las consonantes son menos de 5, rellenar con números aleatorios
        while (strlen($consonants) < self::CODE_LENGTH) {
            $consonants .= rand(0, 9);
        }

        // Retornar los primeros 10 caracteres
        return strtoupper(substr($consonants, 0, self::CODE_LENGTH));
    }
}

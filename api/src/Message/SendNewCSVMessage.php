<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class SendNewCSVMessage
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    public function __construct(
        public readonly string $sellerCode,
        public readonly string $csvCode,
    ) {
    }

    public function getSellerCode(): string
    {
        return $this->sellerCode;
    }

    public function getCsvCode(): string
    {
        return $this->csvCode;
    }
}

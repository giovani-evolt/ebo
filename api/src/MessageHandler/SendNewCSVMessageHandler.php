<?php

namespace App\MessageHandler;

use App\Message\SendNewCSVMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendNewCSVMessageHandler
{
    public function __invoke(SendNewCSVMessage $message): void
    {
        // do something with your message
    }
}

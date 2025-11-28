<?php

namespace App\Namer;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class DirectoryNamer implements DirectoryNamerInterface
{

    public function __construct(
        private Security $security
    )
    {
    }

    public function directoryName(object|array $object, \Vich\UploaderBundle\Mapping\PropertyMapping $mapping): string
    {

        $user = $this->security->getUser();

        $seller = $user->getSeller();

        return $seller->getCode()->toString();
    }
}


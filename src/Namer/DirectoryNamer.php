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
        $code = '';
        if($object->getSeller() !== null){
            $code = $object->getSeller()->getCode()->toString();
        } else {
            $user = $this->security->getUser();
            $seller = $user->getSeller();
            $code = $seller->getCode()->toString();
        }

        return $code;
    }
}


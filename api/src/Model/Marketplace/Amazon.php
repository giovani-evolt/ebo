<?php

namespace App\Model\Marketplace;

class Amazon
{
    public const CODE = 'AMZ';
    public const NAME = 'Amazon';

    public function getReportPath()
    {
        // return dirname(__FILE__).'/../../tests/files/jul.csv';
        return '/app/tests/files/jul.csv';
    }
}
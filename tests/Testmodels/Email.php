<?php

declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\Tests\Testmodels;

use PhilippR\Atk4\SecondaryModel\SecondaryModel;
use PhilippR\Atk4\SecondaryModel\SecondaryModelRelationTrait;

class Email extends SecondaryModel
{

    use SecondaryModelRelationTrait;

    public $table = 'email';

    /**
     * @return void
     */
    protected function init(): void
    {
        parent::init();

        $this->addField('value');
        $this->addField('some_other_field');
    }
}
<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use secondarymodelforatk\SecondaryModel;
use secondarymodelforatk\SecondaryModelRelationTrait;

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

        $this->addField('some_other_field');
    }
}
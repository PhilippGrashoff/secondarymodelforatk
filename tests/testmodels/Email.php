<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use Atk4\Data\Model;
use secondarymodelforatk\SecondaryModel;
use secondarymodelforatk\SecondaryModelRelationTrait;

class Email extends SecondaryModel {

    use SecondaryModelRelationTrait;

    public $table = 'email';

    protected function init(): void
    {
        parent::init();

        $this->addField('some_other_field');
    }
}
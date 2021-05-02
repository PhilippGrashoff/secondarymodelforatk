<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use Atk4\Data\Model;
use secondarymodelforatk\SecondaryModelRelationTrait;

class Company extends Model {

    use SecondaryModelRelationTrait;

    public $table = 'company';

    protected function init(): void
    {
         parent::init();

         $this->addSecondaryModelHasMany(Email::class);
    }
}
<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use atk4\data\Model;
use secondarymodelforatk\SecondaryModelRelationTrait;

class Person extends Model {

    use SecondaryModelRelationTrait;

    public $table = 'person';

    public function init(): void
    {
         parent::init();

         $this->addSecondaryModelHasMany(Email::class);
    }
}
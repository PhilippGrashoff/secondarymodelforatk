<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use Atk4\Data\Model;
use secondarymodelforatk\SecondaryModelRelationTrait;

class Person extends Model {

    use SecondaryModelRelationTrait;

    public $table = 'person';

    protected function init(): void
    {
         parent::init();
         $this->addField('name');
         $this->addSecondaryModelHasMany(Email::class);
    }
}
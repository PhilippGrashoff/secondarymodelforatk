<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use atk4\data\Model;
use secondarymodelforatk\SecondaryModel;
use secondarymodelforatk\SecondaryModelRelationTrait;

class Email extends SecondaryModel {

    use SecondaryModelRelationTrait;

    public $table = 'email';
}
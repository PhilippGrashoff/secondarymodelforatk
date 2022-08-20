<?php

declare(strict_types=1);

namespace secondarymodelforatk\tests\testmodels;

use Atk4\Data\Model;
use secondarymodelforatk\SecondaryModelRelationTrait;


/**
 * Test class to demonstrate how SecondaryModel records can be "added" to a different model if needed.
 * In this case, lets assume admin model is a join of data in admin and person table (not implemented here).
 * When adding an email to an Admin, it should be linked to the underlying Person model.
 */
class Admin extends Model {

    use SecondaryModelRelationTrait;

    public $table = 'admin';

    protected function init(): void
    {
         parent::init();

         $this->hasOne('person_id', ['model' => [Person::class]]);

         $this->addSecondaryModelHasMany(
             Email::class,
             false, //do not delete emails when admin record is deleted
             Person::class, //link to this model
             'person_id' //with this id
         );
    }
}
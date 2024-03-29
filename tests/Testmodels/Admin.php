<?php

declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\Tests\Testmodels;

use Atk4\Core\Exception;
use Atk4\Data\Model;
use PhilippR\Atk4\SecondaryModel\SecondaryModelRelationTrait;


/**
 * Test class to demonstrate how SecondaryModel records can be "added" to a different model if needed.
 * In this case, lets assume admin model is a join of data in admin and person table (not implemented here).
 * When adding an email to an Admin, it should be linked to the underlying Person model.
 */
class Admin extends Model
{

    use SecondaryModelRelationTrait;

    public $table = 'admin';

    /**
     * @return void
     * @throws Exception
     * @throws \Atk4\Data\Exception
     */
    protected function init(): void
    {
        parent::init();

        $this->hasOne('person_id', ['model' => [Person::class]]);

        $this->addSecondaryModelHasMany(
            Email::class,
            false, //do not delete emails when admin record is deleted
            Person::class, //test link to a different model
            'person_id' //with this id
        );
    }
}
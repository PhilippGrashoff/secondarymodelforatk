<?php

declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\Tests\Testmodels;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use PhilippR\Atk4\SecondaryModel\SecondaryModelRelationTrait;

class Company extends Model
{

    use SecondaryModelRelationTrait;

    public $table = 'company';

    /**
     * @return void
     * @throws \Atk4\Core\Exception
     * @throws Exception
     */
    protected function init(): void
    {
        parent::init();

        $this->addField('name');
        $this->addSecondaryModelHasMany(Email::class);
    }
}
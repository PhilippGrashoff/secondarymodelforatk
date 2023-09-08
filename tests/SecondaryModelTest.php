<?php declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\tests;

use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use PhilippR\Atk4\SecondaryModel\ClassNotExistsException;
use PhilippR\Atk4\SecondaryModel\ParentNotFoundException;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Email;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Person;


class SecondaryModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Person($this->db))->create();
        $this->createMigrator(new Email($this->db))->create();
    }

    public function testGetParentEntity(): void
    {
        $person = (new Person($this->db))->createEntity();
        $person->save();
        $email = (new Email($this->db))->createEntity();
        $email->set('model_class', Person::class);
        $email->set('model_id', $person->getId());
        $email->save();
        $parent = $email->getParentEntity();
        self::assertTrue($parent instanceof Person);
        self::assertTrue($parent->isLoaded());
        self::assertSame($person->getId(), $parent->getId());
    }

    public function testGetParentEntityExceptionInvalidModelClass(): void
    {
        $model = (new Person($this->db))->createEntity();
        $model->save();
        $email = (new Email($this->db))->createEntity();
        $email->set('model_class', 'Duggu');
        $email->set('model_id', $model->getId());
        self::expectException(ClassNotExistsException::class);
        $email->getParentEntity();
    }

    public function testParentNotFoundException(): void
    {
        $email = (new Email($this->db))->createEntity();
        $email->set('model_class', Person::class);
        $email->set('model_id', 333);
        $email->save();
        self::expectException(ParentNotFoundException::class);
        $email->getParentEntity();
    }

    public function testSetParentEntity(): void
    {
        $person = (new Person($this->db))->createEntity();
        $person->save();
        $email = (new Email($this->db))->createEntity();
        $email->setParentEntity($person);

        self::assertSame($person->getId(), $email->get('model_id'));
        self::assertSame(Person::class, $email->get('model_class'));
    }
}

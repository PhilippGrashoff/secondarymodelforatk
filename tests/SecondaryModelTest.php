<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use Atk4\Core\Exception;
use atkextendedtestcase\TestCase;
use secondarymodelforatk\ClassNotExistsException;
use secondarymodelforatk\ParentNotFoundException;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;


class SecondaryModelTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        Person::class,
        Email::class
    ];

    public function testGetParentEntity(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person = (new Person($persistence))->createEntity();
        $person->save();
        $email = (new Email($persistence))->createEntity();
        $email->set('model_class', Person::class);
        $email->set('model_id', $person->getId());
        $email->save();
        $parent = $email->getParentEntity();
        self::assertTrue($parent instanceof Person);
        var_dump($parent->get('id'));
        self::assertTrue($parent->isLoaded());
        self::assertSame($person->getId(), $parent->getId());
    }

    public function testGetParentObjectExceptionInvalidModelClass(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = (new Person($persistence))->createEntity();
        $model->save();
        $email = (new Email($persistence))->createEntity();
        $email->set('model_class', 'Duggu');
        $email->set('model_id', $model->getId());
        self::expectException(ClassNotExistsException::class);
        $email->getParentEntity();
    }

    public function testParentNotFoundException(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $email = (new Email($persistence))->createEntity();
        $email->set('model_class', Person::class);
        $email->set('model_id', 333);
        $email->save();
        self::expectException(ParentNotFoundException::class);
        $email->getParentEntity();
    }
}

<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

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

    public function testGetParentObject(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence);
        //no model_class set
        self::assertEquals(null, $email->getParentEntity());

        //model_class, but no id
        $email->set('model_class', Person::class);
        self::assertEquals(null, $email->getParentEntity());

        //Record with valid id
        $email->set('model_id', $model->get('id'));
        $parentObject = $email->getParentEntity();
        self::assertTrue($parentObject instanceof Person);
        self::assertTrue($parentObject->loaded());
        self::assertSame($model->get('id'), $parentObject->get('id'));
    }

    public function testGetParentObjectExceptionInvalidModelClass(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence);
        $email->set('model_class', 'Duggu');
        $email->set('model_id', $model->get('id'));
        self::expectException(ClassNotExistsException::class);
        $email->getParentEntity();
    }

    public function testParentNotFoundException(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $email = new Email($persistence);
        $email->set('model_class', Person::class);
        $email->set('model_id', 333);
        self::expectException(ParentNotFoundException::class);
        $email->getParentEntity();
    }
}

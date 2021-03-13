<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use atk4\core\AtkPhpunit\TestCase;
use atk4\data\Persistence;
use secondarymodelforatk\ClassNotExistsException;
use secondarymodelforatk\ParentNotFoundException;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;


class SecondaryModelTest extends TestCase
{

    public function testGetParentObject()
    {
        $persistence = new Persistence\Array_();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence);
        //no model_class set
        self::assertEquals(null, $email->getParentObject());

        //model_class, but no id
        $email->set('model_class', Person::class);
        self::assertEquals(null, $email->getParentObject());

        //Record with valid id
        $email->set('model_id', $model->get('id'));
        $parentObject = $email->getParentObject();
        self::assertTrue($parentObject instanceof Person);
        self::assertTrue($parentObject->loaded());
        self::assertSame($model->get('id'), $parentObject->get('id'));
    }

    public function testGetParentObjectExceptionInvalidModelClass()
    {
        $persistence = new Persistence\Array_();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence);
        $email->set('model_class', 'Duggu');
        $email->set('model_id', $model->get('id'));
        self::expectException(ClassNotExistsException::class);
        $email->getParentObject();
    }

    public function testGetParentObjectNullOnNonExistingRecord()
    {
        $email = new Email(new Persistence\Array_());
        $email->set('model_class', Person::class);
        $email->set('model_id', 333);
        self::expectException(ParentNotFoundException::class);
        $email->getParentObject();
    }

    public function testSetParentObjectDataDuringInit()
    {
        $persistence = new Persistence\Array_();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence, ['parentObject' => $model]);
        $parentObject = $email->getParentObject();
        self::assertTrue($parentObject instanceof Person);
        self::assertSame($model->get('id'), $parentObject->get('id'));
    }
}

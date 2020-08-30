<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use atk4\data\Exception;
use atk4\core\AtkPhpunit\TestCase;
use atk4\data\Persistence;
use secondarymodelforatk\tests\testmodels\Person;
use secondarymodelforatk\tests\testmodels\Email;


class SecondaryModelTest extends TestCase {

    public function testGetParentObject() {
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
        self::assertTrue($parentObject instanceOf Person);
        self::assertTrue($parentObject->loaded());
        self::assertSame($model->get('id'), $parentObject->get('id'));
    }

    public function testGetParentObjectExceptionInvalidModelClass() {
        $persistence = new Persistence\Array_();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence);
        $email->set('model_class', 'Duggu');
        $email->set('model_id', $model->get('id'));
        self::expectException(Exception::class);
        $email->getParentObject();
    }

    public function testGetParentObjectExceptionInvalidID() {
        $email = new Email(new Persistence\Array_());
        $email->set('model_class', Person::class);
        $email->set('model_id', 333);
        self::expectException(Exception::class);
        $email->getParentObject();
    }

    public function testSetParentObjectDataDuringInit() {
        $persistence = new Persistence\Array_();
        $model = new Person($persistence);
        $model->save();
        $email = new Email($persistence, ['parentObject' => $model]);
        $parentObject = $email->getParentObject();
        self::assertTrue($parentObject instanceOf Person);
        self::assertSame($model->get('id'), $parentObject->get('id'));
    }
}

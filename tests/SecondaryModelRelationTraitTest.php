<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use atk4\data\Exception;
use atk4\core\AtkPhpunit\TestCase;
use atk4\data\Persistence;
use secondarymodelforatk\tests\testmodels\Admin;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;

class SecondaryModelRelationTraitTest extends TestCase {

    public function testHasManyRelationIsAdded() {
        $model = new Person(new Persistence\Array_());
        self::assertTrue($model->hasRef(Email::class));
    }

    public function testaddSBMRecord() {
        $persistence = new Persistence\Array_();
        $emailCount = (int) (new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertSame(
            $emailCount + 1,
            (int) (new Email($persistence))->action('count')->getOne()
        );
        //make sure token has correct model_class and model_id set
        self::assertSame(
            Person::class,
            $email->get('model_class')
        );
        self::assertEquals(
            $model->get('id'),
            $email->get('model_id')
        );
    }

    public function testaddSBMRecordWithNonStandardModelIdAndModelClassFields() {
        $model = new Admin(new Persistence\Array_());
        $model->set('person_id', 456);
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        //make sure token has correct model_class and model_id set
        self::assertSame(
            Person::class,
            $email->get('model_class')
        );
        self::assertEquals(
            456,
            $email->get('model_id')
        );
    }

    public function testaddSBMRecordExceptionInvalidClassName() {
        $model = new Person(new Persistence\Array_());
        self::expectException(Exception::class);
        $model->addSecondaryModelRecord('SomeNonDescendantOfSecondaryModel', 'somevalue');
    }

    public function testaddSBMRecordAddDeleteTrueDeletesSBM() {
        $persistence = new Persistence\Array_();
        $emailCount = (int) (new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $model->save();
        $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertSame(
            $emailCount + 1,
            (int) (new Email($persistence))->action('count')->getOne()
        );
        $model->delete();
        self::assertSame(
            $emailCount,
            (int) (new Email($persistence))->action('count')->getOne()
        );
    }

    public function testaddSBMRecordAddDeleteFalseNotDeletesSBM() {
        $persistence = new Persistence\Array_();
        $emailCount = (int) (new Email($persistence))->action('count')->getOne();
        $model = new Admin($persistence);
        $model->save();
        $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertSame(
            $emailCount + 1,
            (int) (new Email($persistence))->action('count')->getOne()
        );
        $model->delete();
        self::assertSame(
            $emailCount + 1,
            (int) (new Email($persistence))->action('count')->getOne()
        );
    }

    public function testaddSBMRecordSBMIsReturned() {
        $model = new Person(new Persistence\Array_());
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertInstanceOf(Email::class, $email);
    }

    public function testaddSBMRecordIfThisNotLoadedAfterSaveHookAddsSBM() {
        $persistence = new Persistence\Array_();
        $emailCount = (int) (new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $return = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertNull($return);
        self::assertSame(
            $emailCount,
            (int) (new Email($persistence))->action('count')->getOne()
        );
        $model->save();
        self::assertSame(
            $emailCount + 1,
            (int) (new Email($persistence))->action('count')->getOne()
        );
        self::assertEquals(
            1,
            $model->ref(Email::class)->action('count')->getOne()
        );
    }
}
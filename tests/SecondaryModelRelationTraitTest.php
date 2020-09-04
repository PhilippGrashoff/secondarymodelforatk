<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use atk4\data\Exception;
use atk4\core\AtkPhpunit\TestCase;
use atk4\data\Persistence;
use atk4\schema\Migration;
use atk4\schema\PhpunitTestCase;
use secondarymodelforatk\tests\testmodels\Admin;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;

class SecondaryModelRelationTraitTest extends PhpunitTestCase {

    public function testHasManyRelationIsAdded() {
        $model = new Person(new Persistence\Array_());
        self::assertTrue($model->hasRef(Email::class));
    }

    public function testaddSecondaryModelRecord() {
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

    public function testaddSecondaryModelRecordWithAdditionalField() {
        $persistence = new Persistence\Array_();
        $emailCount = (int) (new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $model->save();
        $email = $model->addSecondaryModelRecord(
            Email::class,
            '1234567899',
            ['some_other_field' => 'SomeValue']
        );
        self::assertSame(
            'SomeValue',
            $email->get('some_other_field')
        );
    }

    public function testaddSecondaryModelRecordWithNonStandardModelIdAndModelClassFields() {
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

    public function testaddSecondaryModelRecordExceptionInvalidClassName() {
        $model = new Person(new Persistence\Array_());
        self::expectException(Exception::class);
        $model->addSecondaryModelRecord('SomeNonDescendantOfSecondaryModel', 'somevalue');
    }

    public function testaddSecondaryModelRecordAddDeleteTrueDeletesSBM() {
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

    public function testaddSecondaryModelRecordAddDeleteFalseNotDeletesSBM() {
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

    public function testaddSecondaryModelRecordSBMIsReturned() {
        $model = new Person(new Persistence\Array_());
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertInstanceOf(Email::class, $email);
    }

    public function testaddSecondaryModelRecordIfThisNotLoadedAfterSaveHookAddsSBM() {
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
    
    public function testgetFirstSecondaryModelRecord() {
        $model = new Person(new Persistence\Array_());
        $model->save();
        self::assertNull($model->getFirstSecondaryModelRecord(Email::class));
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        $result = $model->getFirstSecondaryModelRecord(Email::class);
        self::assertInstanceOf(Email::class, $result);
        self::assertSame(
            $email->get('id'),
            $result->get('id')
        );
    }

    public function testgetAllSecondaryModelValuesAsArray() {
        $persistence = Persistence::connect('sqlite::memory:');
        $model = new Person($persistence);
        Migration::of($model)->drop()->create();
        $email = new Email($persistence);
        Migration::of($email)->drop()->create();
        $model->save();
        self::assertSame(
            [],
            $model->getAllSecondaryModelValuesAsArray(Email::class)
        );
        $model->addSecondaryModelRecord(Email::class, '1234567899');
        $model->addSecondaryModelRecord(Email::class, 'asdfgh');
        self::assertEquals(
            2,
            $model->ref(Email::class)->action('count')->getOne()
        );
        self::assertSame(
            ['1234567899', 'asdfgh'],
            $model->getAllSecondaryModelValuesAsArray(Email::class)
        );
    }

    public function testRefConditionsSetupProperly() {
        $persistence = Persistence::connect('sqlite::memory:');
        $model1 = new Person($persistence);
        Migration::of($model1)->drop()->create();
        $email = new Email($persistence);
        Migration::of($email)->drop()->create();
        $model1->save();
        $model1->addSecondaryModelRecord(Email::class, '1234567899');
        $model1->addSecondaryModelRecord(Email::class, 'asdfgh');

        $model2 = new Person($persistence);
        $model2->save();
        $model2->addSecondaryModelRecord(Email::class, 'zireoowej');

        self::assertEquals(
            2,
            $model1->ref(Email::class)->action('count')->getOne()
        );
        self::assertEquals(
            1,
            $model2->ref(Email::class)->action('count')->getOne()
        );

        self::assertSame(
            ['1234567899', 'asdfgh'],
            $model1->getAllSecondaryModelValuesAsArray(Email::class)
        );

        self::assertSame(
            ['zireoowej'],
            $model2->getAllSecondaryModelValuesAsArray(Email::class)
        );
    }
}
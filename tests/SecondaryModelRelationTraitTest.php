<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use Atk4\Data\Exception;
use Atk4\Data\Persistence;
use secondarymodelforatk\tests\testmodels\Admin;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;
use traitsforatkdata\TestCase;

class SecondaryModelRelationTraitTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        Email::class,
        Person::class
    ];

    public function testHasManyRelationIsAdded()
    {
        $model = new Person(new Persistence\Array_());
        self::assertTrue($model->hasRef(Email::class));
    }

    public function testaddSecondaryModelRecord()
    {
        $persistence = $this->getSqliteTestPersistence();
        $emailCount = (int)(new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        self::assertSame(
            Person::class,
            $email->get('model_class')
        );
        self::assertEquals(
            $model->get('id'),
            $email->get('model_id')
        );
    }

    public function testaddSecondaryModelRecordWithAdditionalField()
    {
        $persistence = new Persistence\Array_();
        $emailCount = (int)(new Email($persistence))->action('count')->getOne();
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

    public function testaddSecondaryModelRecordWithNonStandardModelIdAndModelClassFields()
    {
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

    public function testaddSecondaryModelRecordExceptionInvalidClassName()
    {
        $model = new Person(new Persistence\Array_());
        self::expectException(Exception::class);
        $model->addSecondaryModelRecord('SomeNonDescendantOfSecondaryModel', 'somevalue');
    }

    public function testaddSecondaryModelRecordAddDeleteTrueDeletesSBM()
    {
        $persistence = new Persistence\Array_();
        $emailCount = (int)(new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $model->save();
        $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($persistence))->action('count')->getOne()
        );
        $model->delete();
        self::assertSame(
            $emailCount,
            (int)(new Email($persistence))->action('count')->getOne()
        );
    }

    public function testaddSecondaryModelRecordAddDeleteFalseNotDeletesSBM()
    {
        $persistence = new Persistence\Array_();
        $emailCount = (int)(new Email($persistence))->action('count')->getOne();
        $model = new Admin($persistence);
        $model->save();
        $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($persistence))->action('count')->getOne()
        );
        $model->delete();
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($persistence))->action('count')->getOne()
        );
    }

    public function testaddSecondaryModelRecordSBMIsReturned()
    {
        $model = new Person(new Persistence\Array_());
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertInstanceOf(Email::class, $email);
    }

    public function testaddSecondaryModelRecordIfThisNotLoadedAfterSaveHookAddsSBM()
    {
        $persistence = new Persistence\Array_();
        $emailCount = (int)(new Email($persistence))->action('count')->getOne();
        $model = new Person($persistence);
        $return = $model->addSecondaryModelRecord(Email::class, '1234567899');
        self::assertNull($return);
        self::assertSame(
            $emailCount,
            (int)(new Email($persistence))->action('count')->getOne()
        );
        $model->save();
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($persistence))->action('count')->getOne()
        );
        self::assertEquals(
            1,
            $model->ref(Email::class)->action('count')->getOne()
        );
    }

    public function testgetFirstSecondaryModelRecord()
    {
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

    public function testgetAllSecondaryModelValuesAsArray()
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
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

    public function testRefConditionsSetupProperly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $model1 = new Person($persistence);
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

    public function testExceptionThisNotLoadedGetFirst()
    {
        $model1 = new Person(new Persistence\Array_());
        self::expectException(Exception::class);
        $model1->getFirstSecondaryModelRecord(Email::class);
    }

    public function testExceptionThisNotLoadedGetArray()
    {
        $model1 = new Person(new Persistence\Array_());
        self::expectException(Exception::class);
        $model1->getAllSecondaryModelValuesAsArray(Email::class);
    }

    public function testUpdateSecondaryModelRecord()
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        $updatedEmail = $model->updateSecondaryModelRecord(
            Email::class,
            $email->get('id'),
            '987654321',
            ['some_other_field' => 'LALA']
        );

        self::assertInstanceOf(
            Email::class,
            $updatedEmail
        );

        self::assertSame(
            $email->get('id'),
            $updatedEmail->get('id')
        );

        $email->reload();

        self::assertSame(
            '987654321',
            $email->get('value')
        );

        self::assertSame(
            'LALA',
            $email->get('some_other_field')
        );
    }

    public function testDeleteSecondaryModelRecord()
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, '1234567899');
        $deletedEmail = $model->deleteSecondaryModelRecord(Email::class, $email->get('id'));

        self::assertInstanceOf(
            Email::class,
            $deletedEmail
        );

        self::assertEquals(
            $email->get('model_id'),
            $deletedEmail->get('model_id')
        );

        self::assertSame(
            $email->get('model_class'),
            $deletedEmail->get('model_class')
        );

        $email->tryLoad($email->get('id'));

        self::assertFalse($email->loaded());
    }

    public function testExceptionThisNotLoadedUpdateSecondaryModelRecord()
    {
        $model1 = new Person($this->getSqliteTestPersistence());
        self::expectException(Exception::class);
        $model1->updateSecondaryModelRecord(Email::class, 1, 'sdff');
    }

    public function testExceptionThisNotLoadedDeleteSecondaryModelRecord()
    {
        $model1 = new Person($this->getSqliteTestPersistence());
        self::expectException(Exception::class);
        $model1->deleteSecondaryModelRecord(Email::class, 1);
    }

    public function testGetFirstSecondaryModelValue()
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
        $model->save();
        self::assertSame(
            '',
            $model->getFirstSecondaryModelValue(Email::class)
        );


        $email1 = $model->addSecondaryModelRecord(Email::class, '1234567899');
        $email2 = $model->addSecondaryModelRecord(Email::class, 'frfrfrfr');

        $email1->set('value', null);
        $email1->save();
        self::assertSame(
            $email2->get('value'),
            $model->getFirstSecondaryModelValue(Email::class)
        );

        $email1->set('value', '');
        $email1->save();
        self::assertSame(
            $email2->get('value'),
            $model->getFirstSecondaryModelValue(Email::class)
        );
        $email1->set('value', 'nowthereisavalue');
        $email1->save();
        self::assertSame(
            $email1->get('value'),
            $model->getFirstSecondaryModelValue(Email::class)
        );
    }

    public function testExceptionThisNotLoadedGetFirstSecondaryModelValue()
    {
        $model1 = new Person($this->getSqliteTestPersistence());
        self::expectException(Exception::class);
        $model1->getFirstSecondaryModelValue(Email::class);
    }

    public function testNoFieldsDirtyOnLoad()
    {
        $persistence = $this->getSqliteTestPersistence();
        $model = new Person($persistence);
        $model->save();
        $return = $model->addSecondaryModelRecord(Email::class, '1234567899');
        $email = $model->ref(Email::class);
        $email->load($return->get('id'));
        self::assertSame(
            [],
            $email->dirty
        );
    }
}
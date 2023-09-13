<?php declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\tests;

use Atk4\Data\Exception;
use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Admin;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Company;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Email;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Person;

class SecondaryModelRelationTraitTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Person($this->db))->create();
        $this->createMigrator(new Email($this->db))->create();
        $this->createMigrator(new Admin($this->db))->create();
        $this->createMigrator(new Company($this->db))->create();
    }

    public function testHasManyRelationIsAdded(): void
    {
        $model = new Person($this->db);
        self::assertTrue($model->hasReference(Email::class));
    }

    public function testaddSecondaryModelRecord(): void
    {
        $emailCount = (int)(new Email($this->db))->action('count')->getOne();
        $model = (new Person($this->db))->createEntity();
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($this->db))->action('count')->getOne()
        );

        self::assertSame(
            Person::class,
            $email->get('model_class')
        );
        self::assertEquals(
            $model->getId(),
            $email->get('model_id')
        );
    }

    public function testaddSecondaryModelRecordWithAdditionalField(): void
    {
        $model = (new Person($this->db))->createEntity();
        $model->save();
        $email = $model->addSecondaryModelRecord(
            Email::class,
            [
                'value' => 1234567899,
                'some_other_field' => 'SomeValue'
            ]
        );
        self::assertSame(
            'SomeValue',
            $email->get('some_other_field')
        );
    }

    public function testaddSecondaryModelRecordWithNonStandardModelIdAndModelClassFields(): void
    {
        $model = (new Admin($this->db))->createEntity();
        $model->set('person_id', 456);
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
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

    public function testaddSecondaryModelRecordExceptionInvalidClassName(): void
    {
        $model = (new Person($this->db))->createEntity();
        self::expectExceptionMessage('Child element not found');
        $model->addSecondaryModelRecord('SomeNonDescendantOfSecondaryModel', ['value' => 'somevalue']);
    }

    public function testaddSecondaryModelRecordAddDeleteTrueDeletesSBM(): void
    {
        $emailCount = (int)(new Email($this->db))->action('count')->getOne();
        $model = (new Company($this->db))->createEntity();
        $model->save();
        $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($this->db))->action('count')->getOne()
        );
        $model->delete();
        self::assertSame(
            $emailCount,
            (int)(new Email($this->db))->action('count')->getOne()
        );
    }

    public function testAddSecondaryModelRecordAddDeleteFalseNotDeletesSBM(): void
    {
        $emailCount = (int)(new Email($this->db))->action('count')->getOne();
        $model = (new Admin($this->db))->createEntity();
        $model->save();
        $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($this->db))->action('count')->getOne()
        );
        $model->delete();
        self::assertSame(
            $emailCount + 1,
            (int)(new Email($this->db))->action('count')->getOne()
        );
    }

    public function testaddSecondaryModelRecordSBMIsReturned(): void
    {
        $model = (new Person($this->db))->createEntity();
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        self::assertInstanceOf(Email::class, $email);
    }

    public function testRefConditionsSetupProperly(): void
    {
        $model1 = (new Person($this->db))->createEntity();
        $model1->save();
        $model1->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        $model1->addSecondaryModelRecord(Email::class, ['value' => 'asdfgh']);

        $model2 = (new Person($this->db))->createEntity();
        $model2->save();
        $model2->addSecondaryModelRecord(Email::class, ['value' => 'zireoowej']);

        self::assertEquals(
            2,
            $model1->ref(Email::class)->action('count')->getOne()
        );
        self::assertEquals(
            1,
            $model2->ref(Email::class)->action('count')->getOne()
        );
    }


    public function testUpdateSecondaryModelRecord(): void
    {
        $model = (new Person($this->db))->createEntity();
        $model->save();
        $email = $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        $updatedEmail = $model->updateSecondaryModelRecord(
            Email::class,
            $email->getId(),
            [
                'value' => 987654321,
                'some_other_field' => 'LALA'
            ]
        );

        self::assertInstanceOf(
            Email::class,
            $updatedEmail
        );

        self::assertSame(
            $email->getId(),
            $updatedEmail->getId()
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

    public function testDeleteSecondaryModelRecord(): void
    {
        $person = (new Person($this->db))->createEntity();
        $person->save();
        $email = $person->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        $emailId = $email->getId();
        $person->deleteSecondaryModelRecord(Email::class, $email->getId());

        self::assertSame(
            0,
            (int)(new Email($this->db))->action('count')->getOne()
        );

        $newEmail = (new Email($this->db));
        self::expectExceptionMessage('Record with specified ID was not found');
        $newEmail->load($emailId);
    }

    public function testExceptionThisNotLoadedUpdateSecondaryModelRecord(): void
    {
        $model1 = new Person($this->db);
        self::expectException(Exception::class);
        $model1->updateSecondaryModelRecord(Email::class, 1, ['value' => 'sdff']);
    }

    public function testExceptionThisNotLoadedDeleteSecondaryModelRecord(): void
    {
        $model1 = new Person($this->db);
        self::expectException(Exception::class);
        $model1->deleteSecondaryModelRecord(Email::class, 1);
    }

    public function testNoFieldsDirtyOnLoad(): void
    {
        $model = (new Person($this->db))->createEntity();
        $model->save();
        $return = $model->addSecondaryModelRecord(Email::class, ['value' => 1234567899]);
        $email = $model->ref(Email::class)->load($return->getId());
        self::assertSame(
            [],
            $email->getDirtyRef()
        );
    }
}
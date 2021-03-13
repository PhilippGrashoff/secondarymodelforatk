<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use secondarymodelforatk\ParentExistsChecker;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;
use traitsforatkdata\TestCase;


class ParentExistsCheckerTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        Person::class,
        Email::class
    ];

    public function testCheckLastCheckedIsSet(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person = new Person($persistence);
        $person->save();
        $email = $person->addSecondaryModelRecord(Email::class, 'LALA');
        self::assertNull($email->get('last_checked'));

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence));
        $email->reload();
        self::assertSame(
            $email->get('last_checked')->format(DATE_ATOM),
            (new \DateTime())->format(DATE_ATOM)
        );
    }

    public function testOnlyRecordsWithoutParentAreDeleted(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person1 = new Person($persistence);
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $person2 = new Person($persistence);
        $person2->save();
        $email3 = $person2->addSecondaryModelRecord(Email::class, 'LALA');
        $email4 = $person2->addSecondaryModelRecord(Email::class, 'LALA');
        $person3 = new Person($persistence);
        $person3->save();
        $email5 = $person3->addSecondaryModelRecord(Email::class, 'LALA');
        self::assertSame(
            5,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        //hack to not execute hooks
        (new Person($persistence))->addCondition('id', '=', $person2->getId())->action('delete')->execute();
        self::assertSame(
            2,
            (int)(new Person($persistence))->action('count')->getOne()
        );

        self::assertSame(
            5,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence));

        self::assertSame(
            3,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        self::assertTrue((new Email($persistence))->tryLoad($email1->getId())->loaded());
        self::assertTrue((new Email($persistence))->tryLoad($email2->getId())->loaded());
        self::assertTrue((new Email($persistence))->tryLoad($email5->getId())->loaded());
        self::assertFalse((new Email($persistence))->tryLoad($email3->getId())->loaded());
        self::assertFalse((new Email($persistence))->tryLoad($email4->getId())->loaded());
    }

    public function testOrderCheckOldestFirst(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person1 = new Person($persistence);
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');

        $pec = new ParentExistsChecker(['amountRecordsToCheck' => 1]);
        $now = new \DateTime();
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence));
        $email1->reload();
        $email2->reload();
        self::assertEqualsWithDelta(
            (int)$email1->get('last_checked')->format('Hisv'),
            (int)$now->format('Hisv'),
            50
        );
        self::assertNull($email2->get('last_checked'));

        sleep(1);
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence));
        $email2->reload();
        self::assertEqualsWithDelta(
            (int)$email2->get('last_checked')->format('Hisv'),
            (int)(clone $now)->modify('+1 Second')->format('Hisv'),
            100
        );

        sleep(1);
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence));
        $email1->reload();
        self::assertEqualsWithDelta(
            (int)$email1->get('last_checked')->format('Hisv'),
            (int)(clone $now)->modify('+2 Seconds')->format('Hisv'),
            150
        );
    }

    public function testSecondaryModelsWithModelClassNullAreNotTouched(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person1 = new Person($persistence);
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email3 = (new Email($persistence))->save();
        //hack to not execute hooks
        (new Person($persistence))->addCondition('id', '=', $person1->getId())->action('delete')->execute();
        self::assertSame(
            3,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence));
        self::assertSame(
            1,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        $email3->reload();
        self::assertEqualsWithDelta(
            (int)$email3->get('last_checked')->format('Hisv'),
            (int)(new \DateTime())->format('Hisv'),
            50
        );
    }
}

<?php declare(strict_types=1);

namespace secondarymodelforatk\tests;

use secondarymodelforatk\ParentExistsChecker;
use secondarymodelforatk\tests\testmodels\Email;
use secondarymodelforatk\tests\testmodels\Person;
use atkextendedtestcase\TestCase;


class ParentExistsCheckerTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        Person::class,
        Email::class
    ];

    public function testCheckLastCheckedIsSet(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person = (new Person($persistence))->createEntity();
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
        $person1 = (new Person($persistence))->createEntity();
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $person2 = (new Person($persistence))->createEntity();
        $person2->save();
        $email3 = $person2->addSecondaryModelRecord(Email::class, 'LALA');
        $email4 = $person2->addSecondaryModelRecord(Email::class, 'LALA');
        $person3 = (new Person($persistence))->createEntity();
        $person3->save();
        $email5 = $person3->addSecondaryModelRecord(Email::class, 'LALA');
        self::assertSame(
            5,
            (int)(new Email($persistence))->action('count')->getOne()
        );

        $person2->delete();
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

        self::assertTrue((new Email($persistence))->tryLoad($email1->getId())->isLoaded());
        self::assertTrue((new Email($persistence))->tryLoad($email2->getId())->isLoaded());
        self::assertTrue((new Email($persistence))->tryLoad($email5->getId())->isLoaded());
        self::assertNull((new Email($persistence))->tryLoad($email3->getId()));
        self::assertNull((new Email($persistence))->tryLoad($email4->getId()));
    }

    public function testOrderCheckOldestFirst(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $person1 = (new Person($persistence))->createEntity();
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');

        $pec = new ParentExistsChecker();
        $now = new \DateTime();
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence), 1);
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

    public function testSetAmountToCheckAsParam(): void {
        $persistence = $this->getSqliteTestPersistence();
        $person1 = (new Person($persistence))->createEntity();
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($persistence), 1);

        $email1->reload();
        $email2->reload();
        self::assertEqualsWithDelta(
            (int)$email1->get('last_checked')->format('Hisv'),
            (int)(new \DateTime())->format('Hisv'),
            50
        );
        self::assertNull($email2->get('last_checked'));
    }
}

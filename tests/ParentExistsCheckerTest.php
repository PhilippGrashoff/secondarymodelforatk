<?php declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\tests;

use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use DateTime;
use PhilippR\Atk4\SecondaryModel\ParentExistsChecker;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Email;
use PhilippR\Atk4\SecondaryModel\Tests\Testmodels\Person;

class ParentExistsCheckerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Person($this->db))->create();
        $this->createMigrator(new Email($this->db))->create();
    }

    public function testCheckLastCheckedIsSet(): void
    {
        $person = (new Person($this->db))->createEntity();
        $person->save();
        $email = $person->addSecondaryModelRecord(Email::class, 'LALA');
        self::assertNull($email->get('last_checked'));

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($this->db));
        $email->reload();
        self::assertSame(
            $email->get('last_checked')->format(DATE_ATOM),
            (new DateTime())->format(DATE_ATOM)
        );
    }

    public function testOnlyRecordsWithoutParentAreDeleted(): void
    {
        $person1 = (new Person($this->db))->createEntity();
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $person2 = (new Person($this->db))->createEntity();
        $person2->save();
        $email3 = $person2->addSecondaryModelRecord(Email::class, 'LALA');
        $email4 = $person2->addSecondaryModelRecord(Email::class, 'LALA');
        $person3 = (new Person($this->db))->createEntity();
        $person3->save();
        $email5 = $person3->addSecondaryModelRecord(Email::class, 'LALA');
        self::assertSame(
            5,
            (int)(new Email($this->db))->action('count')->getOne()
        );

        $person2->delete();
        self::assertSame(
            2,
            (int)(new Person($this->db))->action('count')->getOne()
        );

        self::assertSame(
            5,
            (int)(new Email($this->db))->action('count')->getOne()
        );

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($this->db));

        self::assertSame(
            3,
            (int)(new Email($this->db))->action('count')->getOne()
        );

        self::assertTrue((new Email($this->db))->tryLoad($email1->getId())->isLoaded());
        self::assertTrue((new Email($this->db))->tryLoad($email2->getId())->isLoaded());
        self::assertTrue((new Email($this->db))->tryLoad($email5->getId())->isLoaded());
        self::assertNull((new Email($this->db))->tryLoad($email3->getId()));
        self::assertNull((new Email($this->db))->tryLoad($email4->getId()));
    }

    public function testOrderCheckOldestFirst(): void
    {
        $person1 = (new Person($this->db))->createEntity();
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');

        $pec = new ParentExistsChecker();
        $now = new DateTime();
        $pec->deleteSecondaryModelsWithoutParent(new Email($this->db), 1);
        $email1->reload();
        $email2->reload();
        self::assertEqualsWithDelta(
            (int)$email1->get('last_checked')->format('Hisv'),
            (int)$now->format('Hisv'),
            50
        );
        self::assertNull($email2->get('last_checked'));

        sleep(1);
        $pec->deleteSecondaryModelsWithoutParent(new Email($this->db));
        $email2->reload();
        self::assertEqualsWithDelta(
            (int)$email2->get('last_checked')->format('Hisv'),
            (int)(clone $now)->modify('+1 Second')->format('Hisv'),
            100
        );

        sleep(1);
        $pec->deleteSecondaryModelsWithoutParent(new Email($this->db));
        $email1->reload();
        self::assertEqualsWithDelta(
            (int)$email1->get('last_checked')->format('Hisv'),
            (int)(clone $now)->modify('+2 Seconds')->format('Hisv'),
            150
        );
    }

    public function testSetAmountToCheckAsParam(): void
    {
        $person1 = (new Person($this->db))->createEntity();
        $person1->save();
        $email1 = $person1->addSecondaryModelRecord(Email::class, 'LALA');
        $email2 = $person1->addSecondaryModelRecord(Email::class, 'LALA');

        $pec = new ParentExistsChecker();
        $pec->deleteSecondaryModelsWithoutParent(new Email($this->db), 1);

        $email1->reload();
        $email2->reload();
        self::assertEqualsWithDelta(
            (int)$email1->get('last_checked')->format('Hisv'),
            (int)(new DateTime())->format('Hisv'),
            50
        );
        self::assertNull($email2->get('last_checked'));
    }
}

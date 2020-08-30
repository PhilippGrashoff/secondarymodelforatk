<?php declare(strict_types=1);

namespace PMRAtk\tests\phpunit\Data;

use atk4\data\Exception;
use PMRAtk\tests\phpunit\TestCase;
use PMRAtk\tests\TestClasses\BaseModelClasses\BaseModelA;
use PMRAtk\tests\TestClasses\BaseModelClasses\SecondaryModelA;


class SecondaryModelTest extends TestCase {

    public function testGetParentObject() {
        $baseModelA = new BaseModelA(self::$app->db);
        $baseModelA->save();
        $secondaryModelA = new SecondaryModelA(self::$app->db);
        //no model_class set
        $this->assertEquals(null, $secondaryModelA->getParentObject());

        //model_class, but no id
        $secondaryModelA->set('model_class', BaseModelA::class);
        $this->assertEquals(null, $secondaryModelA->getParentObject());

        //Record with valid id
        $secondaryModelA->set('model_id', $baseModelA->get('id'));
        $parentObject = $secondaryModelA->getParentObject();
        $this->assertTrue($parentObject instanceOf BaseModelA);
        $this->assertTrue($parentObject->loaded());
    }

    public function testGetParentObjectExceptionInvalidModelClass() {
        $baseModelA = new BaseModelA(self::$app->db);
        $baseModelA->save();
        $secondaryModelA = new SecondaryModelA(self::$app->db);
        $secondaryModelA->set('model_class', 'Duggu');
        $secondaryModelA->set('model_id', $baseModelA->get('id'));
        $this->expectException(Exception::class);
        $secondaryModelA->getParentObject();
    }

    public function testGetParentObjectExceptionInvalidID() {
        $secondaryModelA = new SecondaryModelA(self::$app->db);
        $secondaryModelA->set('model_class', BaseModelA::class);
        $secondaryModelA->set('model_id', 333);
        $this->expectException(Exception::class);
        $secondaryModelA->getParentObject();
    }

    public function testSetParentObjectDataDuringInit() {
        $bm = new BaseModelA(self::$app->db);
        $bm->save();
        $e = new SecondaryModelA(self::$app->db, ['parentObject' => $bm]);
        $g = $e->getParentObject();
        $this->assertTrue($g instanceOf BaseModelA);
    }

    public function testTrimValueOnSave() {
        $e = new SecondaryModelA(self::$app->db);
        $e->set('value', '  whitespace@beforeandafter.com  ');
        $e->save();
        $this->assertSame(
            $e->get('value'),
            'whitespace@beforeandafter.com'
        );
    }
}

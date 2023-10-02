<?php declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel;

use Atk4\Core\Exception;
use Atk4\Data\Model;


abstract class SecondaryModel extends Model
{

    //no expressions, hence no reload needed
    public bool $reloadAfterSave = false;

    protected function init(): void
    {
        parent::init();

        //The class of the parent model e.g. \Some\NameSpace\SomeClass
        $this->addField(
            'model_class',
            [
                'type' => 'string',
                'system' => true
            ]
        );

        //The ID of the parent model, e.g. 159
        $this->addField(
            'model_id',
            [
                'type' => 'integer',
                'system' => true
            ]
        );

        //A parent model, when deleted, SHOULD delete all referenced SecondaryModels like Emails, Addresses, Files.
        //However, periodically checking if parent entity still exists is sensible to avoid having old data without
        //existing parent models. This timestamp can be used to indicate the last time such a check happened.
        $this->addField(
            'last_checked',
            [
                'type' => 'datetime',
                'system' => true,
            ]
        );
    }

    /**
     * tries to load its parent entity based on model_class and model_id
     * @throws ParentNotFoundException
     * @throws ClassNotExistsException
     */
    public function getParentEntity(): Model
    {
        $this->assertIsLoaded();
        /** @var class-string<Model> $className */
        $className = $this->get('model_class');
        if (!class_exists($className)) {
            throw new ClassNotExistsException('Class ' . $className . ' does not exist in ' . __FUNCTION__);
        }

        try {
            /** @var Model $parentEntity */
            $parentEntity = (new $className($this->getModel()->getPersistence()))->load($this->get('model_id'));
            return $parentEntity;
        } catch (\Exception) {
            throw new ParentNotFoundException(
                'Entity of class ' . $className . ' with ID ' . $this->get('model_id') . ' not found'
            );
        }
    }

    /**
     * @throws Exception
     * @throws \Atk4\Data\Exception
     */
    public function setParentEntity(Model $entity): void
    {
        $entity->assertIsLoaded();
        $this->set('model_class', get_class($entity));
        $this->set('model_id', $entity->getId());
    }
}

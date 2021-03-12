<?php declare(strict_types=1);

namespace secondarymodelforatk;

use atk4\data\Exception;
use atk4\data\Model;


abstract class SecondaryModel extends Model
{

    public $parentObject = null;

    protected function init(): void
    {
        parent::init();

        //no expressions, hence no reload needed
        $this->reload_after_save = false;

        $this->addFields(
            [
                //The class of the parent model e.g. Some\NameSpace\SomeClass
                [
                    'model_class',
                    'type' => 'text',
                    'system' => true
                ],
                //The id of the parent model, e.g. 159
                [
                    'model_id',
                    'type' => 'integer',
                    'system' => true
                ],
                //some generic value field for storing the actual data like an address or a phone
                [
                    'value',
                    'type' => 'text'
                ],
                //A parent model, when deleted, SHOULD delete all referenced SecondaryModels like Emails, Addresses, Files.
                //However, periodically checking if parent object still exists is sensible to avoid having old data without
                //existing parent models. This timestamp can be used to indicate the last time such a check happened.
                [
                    'last_checked',
                    'type' => 'datetime',
                    'system' => true,
                ],
            ]
        );

        //set model_class and model_id if parentObject was set. If parentObject is already set,
        //data is automatically pulled
        if ($this->parentObject instanceof Model) {
            $this->set('model_class', get_class($this->parentObject));
            if ($this->parentObject->get($this->parentObject->id_field)) {
                $this->set('model_id', ($this->parentObject)->get($this->parentObject->id_field));
            }
        }

        //hack to get model_class and model_id away from dirty. Its set if parentObject is set
        //TODO: create issue in atk data and remove this when issue is resolved
        $this->onHook(
            Model::HOOK_AFTER_LOAD,
            function(self $model) {
                $model->dirty = [];
            }
        );
    }


    /**
     * tries to load its parent object based on model_class and model_id
     */
    public function getParentObject(): ?Model
    {
        if (
            $this->get('model_class') === null
            || $this->get('model_id') === null
        ) {
            return null;
        }

        $className = $this->get('model_class');
        if (!class_exists($className)) {
            throw new Exception('Class ' . $className . ' does not exist in ' . __FUNCTION__);
        }

        $parentObject = new $className($this->persistence);
        $parentObject->tryLoad($this->get('model_id'));

        if(!$parentObject->loaded()) {
            return null;
        }

        return $parentObject;
    }
}

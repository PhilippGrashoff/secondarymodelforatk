<?php declare(strict_types=1);

namespace secondarymodelforatk;

use atk4\data\Model;
use secondarymodelforatk\Reference\HasManySecondaryModel;
use secondarymodelforatk\SecondaryModel;


/**
 * EPA is short for Email, Address, Phone. This adds functions to add,
 * alter and delete related EPAs
 */
trait SecondaryModelRelationTrait
{

    /**
     * use this in init() to quickly setup a relation to a SecondaryModel like Token
     */
    protected function addSecondaryModelHasMany(
        string $className,
        bool $addDelete = true,
        string $ourClassName = '',
        string $ourIdField = null
    ): HasManySecondaryModel {
        $reference = $this->_hasReference(
            [HasManySecondaryModel::class],
            $className,
            [
                function () use ($className, $ourClassName, $ourIdField) {
                    return (new $className($this->persistence, ['parentObject' => $this]))
                        ->addCondition('model_class', ($ourClassName ? : get_class($this))
                        );
                },
                'their_field'     => 'model_id',
                'our_field'       => $ourIdField === null ? $this->id_field : $ourIdField,
                'our_model_class' => $ourClassName ? : get_class($this)
            ]
        );

        //add a hook that automatically deletes SecondaryModel records when main record is deleted
        if($addDelete) {
            $this->onHook(
                Model::HOOK_AFTER_DELETE,
                function (Model $model) use ($className) {
                    foreach($model->ref($className) as $sbm) {
                        $sbm->delete();
                    }
                }
            );
        }

        return $reference;
    }


    /**
     *
     */
    public function addSecondaryModelRecord(string $className, $value, array $additionalValues = []): ?SecondaryModel {
        if(!$this->hasRef($className)) {
            throw new \atk4\data\Exception('Reference ' . $className . ' does not exist in ' . get_class($this));
        }

        //if $this was not saved yet, add Hook to create SBM after saving
        if(!$this->loaded()) {
            $this->onHook(
                Model::HOOK_AFTER_SAVE,
                function ($model, $isUpdate) use ($className, $value, $additionalValues) {
                    $model->addSecondaryModelRecord($className, $value, $additionalValues);
                }
            );

            return null;
        }

        $sbm = new $className($this->persistence, ['parentObject' => $this]);
        $sbm->set('value', $value);
        $sbm->set('model_id', $this->get($this->getRef($className)->getOurField()));
        $sbm->set('model_class', $this->getRef($className)->getOurModelClass());
        foreach($additionalValues as $fieldName => $fieldValue) {
            $sbm->set($fieldName, $fieldValue);
        }
        $sbm->save();

        return $sbm;
    }
}

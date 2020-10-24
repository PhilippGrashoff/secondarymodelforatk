<?php declare(strict_types=1);

namespace secondarymodelforatk;

use atk4\data\Exception;
use atk4\data\Model;
use secondarymodelforatk\Reference\HasManySecondaryModel;


trait SecondaryModelRelationTrait
{

    /**
     * use this in init() to quickly setup a relation to a SecondaryModel like Token.
     * The only needed parameter is the className of the SecondaryMode.
     *
     * @param string $className The class name of the SecondaryModel, e.g. Email::class
     * @param bool $addDelete if true, a hook to delete all linked SecondaryModels when record is deleted is added
     * @param string $ourClassName defaults to get_class($this). Set differently if you want model_class field of
     *     SecondaryModel filled differently.
     * @param string $ourIdField defaults to $this->id_field. Set differently if you want model_id field of
     *     SecondaryModel filled with the value of a different field.
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
                        ->addCondition(
                            'model_class',
                            ($ourClassName ?: get_class($this))
                        );
                },
                'their_field' => 'model_id',
                'our_field' => $ourIdField === null ? $this->id_field : $ourIdField,
                'our_model_class' => $ourClassName ?: get_class($this)
            ]
        );

        //add a hook that automatically deletes SecondaryModel records when main record is deleted
        if ($addDelete) {
            $this->onHook(
                Model::HOOK_BEFORE_DELETE,
                function (self $model) use ($className) {
                    foreach ($model->ref($className) as $sbm) {
                        $sbm->delete();
                    }
                }
            );
        }

        return $reference;
    }

    /**
     * Add a new SecondayModel record which is linked to $this, e.g. add an Email to a Person.
     * @param string $className The className of the SecondaryModel
     * @param $value //each SecondaryModel has a value field. This content will be set to value field.
     * @param array $additionalValues //if additional field values should be set, use this optional array.
     *     ['some_other_field' => 'SomeValue', 'and_another_field' => 'AndSomeOtherValue']
     */
    public function addSecondaryModelRecord(
        string $className,
        $value,
        array $additionalValues = []
    ): ?SecondaryModel {
        if (!$this->hasRef($className)) {
            throw new Exception('Reference ' . $className . ' does not exist in ' . get_class($this));
        }

        //if $this was not saved yet, add Hook to create SBM after saving
        if (!$this->loaded()) {
            $this->onHook(
                Model::HOOK_AFTER_SAVE,
                function ($model, $isUpdate) use ($className, $value, $additionalValues) {
                    $model->addSecondaryModelRecord($className, $value, $additionalValues);
                }
            );

            return null;
        }

        $secondaryModel = new $className($this->persistence, ['parentObject' => $this]);
        $secondaryModel->set('value', $value);
        $secondaryModel->set('model_id', $this->get($this->getRef($className)->getOurFieldName()));
        $secondaryModel->set('model_class', $this->getRef($className)->getOurModelClass());
         foreach ($additionalValues as $fieldName => $fieldValue) {
            $secondaryModel->set($fieldName, $fieldValue);
        }
        $secondaryModel->save();

        return $secondaryModel;
    }

    /**
     * shortcut to get the first SecondaryModel Record if available. Handy if e.g. you want to load
     * the first email existing for a person.
     */
    public function getFirstSecondaryModelRecord(string $className): ?SecondaryModel
    {
        if (!$this->loaded()) {
            throw new Exception('$this must be loaded in ' . __FUNCTION__);
        }
        //will throw exception if ref does not exist
        $secondaryModel = $this->ref($className);
        $secondaryModel->tryLoadAny();

        if (!$secondaryModel->loaded()) {
            return null;
        }

        return $secondaryModel;
    }

    /**
     * get value field of all SecondaryModels as array. Handy as shortcut if e.g. you want to quickly get all
     * email addresses of a user
     */
    public function getAllSecondaryModelValuesAsArray(string $className): array
    {
        if (!$this->loaded()) {
            throw new Exception('$this must be loaded in ' . __FUNCTION__);
        }
        return array_map(
            function ($a) {
                return $a['value'];
            },
            //will throw exception if ref does not exist
            $this->ref($className)->export(['value'])
        );
    }

    public function updateSecondaryModelRecord(
        string $className,
        $id,
        string $value,
        array $additionalValues = []
    ): SecondaryModel {
        if (!$this->loaded()) {
            throw new Exception('$this must be loaded in ' . __FUNCTION__);
        }
        //will throw exception if ref does not exist
        $secondaryModel = $this->ref($className);
        $secondaryModel->load($id);
        $secondaryModel->set('value', $value);
        $secondaryModel->setMulti($additionalValues);
        $secondaryModel->save();

        return $secondaryModel;
    }

    public function deleteSecondaryModelRecord(
        string $className,
        $id
    ): SecondaryModel {
        if (!$this->loaded()) {
            throw new Exception('$this must be loaded in ' . __FUNCTION__);
        }
        //will throw exception if ref does not exist
        $secondaryModel = $this->ref($className);
        $secondaryModel->load($id);
        $clone = clone $secondaryModel;
        $secondaryModel->delete();

        return $clone;
    }
}

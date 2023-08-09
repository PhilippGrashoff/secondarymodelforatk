<?php

declare(strict_types=1);

namespace secondarymodelforatk;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use secondarymodelforatk\Reference\HasManySecondaryModel;

/**
 * @extends Model<Model>
 */
trait SecondaryModelRelationTrait
{

    /**
     * use this in init() to quickly set up a relation to a SecondaryModel like Token.
     * The only needed parameter is the className of the SecondaryMode.
     *
     * @param class-string<SecondaryModel> $className The class name of the SecondaryModel, e.g. Email::class
     * @param bool $addDeleteHook if true, a hook to delete all linked SecondaryModels when record is deleted is added
     * @param string $ourClassName defaults to get_class($this). Set differently if you want model_class field of
     *     SecondaryModel filled differently.
     * @param ?string $ourIdField defaults to $this->id_field. Set differently if you want model_id field of
     *     SecondaryModel filled with the value of a different field.
     * @return HasManySecondaryModel
     * @throws Exception
     * @throws \Atk4\Core\Exception
     */
    protected function addSecondaryModelHasMany(
        string $className,
        bool $addDeleteHook = true,
        string $ourClassName = '',
        ?string $ourIdField = null
    ): HasManySecondaryModel {#
        /** @var HasManySecondaryModel $reference */
        $reference = $this->_addReference(
            [HasManySecondaryModel::class],
            $className,
            [
                'model' => function () use ($className, $ourClassName) {
                    return (new $className($this->getPersistence()))
                        ->addCondition(
                            'model_class',
                            ($ourClassName ?: get_class($this))
                        );
                },
                'theirField' => 'model_id',
                'ourField' => $ourIdField === null ? $this->idField : $ourIdField,
                'ourModelClass' => $ourClassName ?: get_class($this)
            ]
        );

        //add a hook that automatically deletes SecondaryModel records when main record is deleted
        if ($addDeleteHook) {
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
     * Add a new SecondaryModel record which is linked to $this, e.g. add an Email to a Person.
     * @param class-string<SecondaryModel> $className The className of the SecondaryModel
     * @param string|int|float $value //each SecondaryModel has a value field. This content will be set to value field.
     * @param array<string, string> $additionalValues //if additional field values should be set, use this optional array.
     *     ['some_other_field' => 'SomeValue', 'and_another_field' => 'AndSomeOtherValue']
     * @throws Exception|\Atk4\Core\Exception
     */
    public function addSecondaryModelRecord(
        string $className,
        string|int|float $value,
        array $additionalValues = []
    ): SecondaryModel {
        /** @var HasManySecondaryModel $secondaryModelReference */
        $secondaryModelReference = $this->getModel()->getReference($className);
        $secondaryModel = (new $className($this->getPersistence()))->createEntity();
        $secondaryModel->set('value', $value);
        $secondaryModel->set('model_id', $this->get($secondaryModelReference->getOurFieldName()));
        $secondaryModel->set('model_class', $secondaryModelReference->getOurModelClass());
        foreach ($additionalValues as $fieldName => $fieldValue) {
            $secondaryModel->set($fieldName, $fieldValue);
        }
        $secondaryModel->save();

        return $secondaryModel;
    }

    /**
     * @param string $className
     * @param string|int $id
     * @param string|int|float $value
     * @param array<string, string> $additionalValues
     * @return SecondaryModel
     * @throws Exception
     * @throws \Atk4\Core\Exception
     */
    public function updateSecondaryModelRecord(
        string $className,
        string|int $id,
        string|int|float $value,
        array $additionalValues = []
    ): SecondaryModel {
        $this->assertIsLoaded();
        /** @var SecondaryModel $secondaryModel */
        $secondaryModel = $this->ref($className)->load($id);
        $secondaryModel->set('value', $value);
        foreach ($additionalValues as $fieldName => $fieldValue) {
            $secondaryModel->set($fieldName, $fieldValue);
        }
        $secondaryModel->save();

        return $secondaryModel;
    }

    /**
     * @throws Exception
     */
    public function deleteSecondaryModelRecord(
        string $className,
        string|int $id
    ): SecondaryModel {
        $this->assertIsLoaded();
        /** @var SecondaryModel $secondaryModel */
        $secondaryModel = $this->ref($className)->load($id);
        $secondaryModel->delete();

        return $secondaryModel;
    }
    /**
     * shortcut to get the first SecondaryModel Record if available. Handy if e.g. you want to load
     * the first email existing for a person.
     */
    /*public function getFirstSecondaryModelRecord(string $className): ?SecondaryModel
    {
        $this->assertIsLoaded();

        //will throw exception if ref does not exist
        $secondaryModel = $this->ref($className);
        $secondaryModel->tryLoadAny();

        if (!$secondaryModel->loaded()) {
            return null;
        }

        return $secondaryModel;
    }

    /**
     * get the first value field that is not empty. Handy for e.g. getting the first Phone Number etc.
     */
    /*public function getFirstSecondaryModelValue(string $className): string
    {
        $this->assertIsLoaded();
        //will throw exception if ref does not exist
        $secondaryModel = $this->ref($className);
        $secondaryModel->addCondition('value', '!=', null);
        $secondaryModel->addCondition('value', '!=', '');
        $secondaryModel->tryLoadAny();

        if (!$secondaryModel->loaded()) {
            return '';
        }

        return $secondaryModel->get('value');
    }

    /**
     * get value field of all SecondaryModels as array. Handy as shortcut if e.g. you want to quickly get all
     * email addresses of a user
     */
    /*public function getAllSecondaryModelValuesAsArray(string $className): array
    {
        $this->assertIsEntity();
        return array_map(
            function ($a) {
                return $a['value'];
            },
            //will throw exception if ref does not exist
            $this->ref($className)->export(['value'])
        );
    }
    */
}

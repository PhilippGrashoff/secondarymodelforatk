<?php

declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use PhilippR\Atk4\SecondaryModel\Reference\HasManySecondaryModel;

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
     * @param array<string, string> $values //if additional field values should be set, use this optional array.
     *     ['some_other_field' => 'SomeValue', 'and_another_field' => 'AndSomeOtherValue']
     * @throws Exception|\Atk4\Core\Exception
     */
    public function addSecondaryModelRecord(string $className, array $values = []): SecondaryModel
    {
        $this->assertIsLoaded();
        /** @var HasManySecondaryModel $secondaryModelReference */
        $secondaryModelReference = $this->getModel()->getReference($className);
        $secondaryModel = (new $className($this->getModel()->getPersistence()))->createEntity();
        $secondaryModel->set('model_id', $this->get($secondaryModelReference->getOurFieldName()));
        $secondaryModel->set('model_class', $secondaryModelReference->getOurModelClass());
        foreach ($values as $fieldName => $fieldValue) {
            $secondaryModel->set($fieldName, $fieldValue);
        }
        $secondaryModel->save();

        return $secondaryModel;
    }

    /**
     * @param string $className
     * @param int $id
     * @param array<string, string> $values
     * @return SecondaryModel
     * @throws Exception
     * @throws \Atk4\Core\Exception
     */
    public function updateSecondaryModelRecord(string $className, int $id, array $values = []): SecondaryModel
    {
        $this->assertIsLoaded();
        /** @var SecondaryModel $secondaryModel */
        $secondaryModel = $this->ref($className)->load($id);
        foreach ($values as $fieldName => $fieldValue) {
            $secondaryModel->set($fieldName, $fieldValue);
        }
        $secondaryModel->save();

        return $secondaryModel;
    }

    /**
     * @throws Exception
     */
    public function deleteSecondaryModelRecord(string $className, int $id): SecondaryModel
    {
        $this->assertIsLoaded();
        /** @var SecondaryModel $secondaryModel */
        $secondaryModel = $this->ref($className)->load($id);
        $secondaryModel->delete();

        return $secondaryModel;
    }
}

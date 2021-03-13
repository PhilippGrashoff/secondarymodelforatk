<?php

declare(strict_types=1);

namespace secondarymodelforatk;

use Atk4\Core\DiContainerTrait;

/**
 * This class can be used to check, e.g. in cronjobs, if SecondaryModels without parent exist.
 * The field last_checked is used to indicate when the record was last checked.
 * In theory, a SecondaryModel without a parent should not exist.
 */
class ParentExistsChecker
{

    use DiContainerTrait;

    protected int $amountRecordsToCheck = 100;

    public function __construct(array $defaults = [])
    {
        $this->setDefaults($defaults);
    }

    public function deleteSecondaryModelsWithoutParent(SecondaryModel $model): array
    {
        $deletedRecords = [];
        $model->setLimit($this->amountRecordsToCheck);
        $model->setOrder(['last_checked' => 'asc', $model->id_field => 'asc']);
        foreach ($model as $record) {
            try {
                $record->getParentObject();
                $record->set('last_checked', new \DateTime());
                $record->save();
            } catch (ParentNotFoundException $e) {
                $deletedRecords[] = clone $record;
                $record->delete();
            }
        }

        return $deletedRecords;
    }
}
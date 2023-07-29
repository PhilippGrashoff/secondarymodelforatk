<?php

declare(strict_types=1);

namespace secondarymodelforatk;

/**
 * This class can be used to check, e.g. in cronjobs, if SecondaryModels without parent exist.
 * The field last_checked is used to indicate when the record was last checked.
 * In theory, a SecondaryModel without a parent should not exist.
 */
class ParentExistsChecker
{

    public function deleteSecondaryModelsWithoutParent(SecondaryModel $model, int $amount = 100): array
    {
        $deletedRecords = [];
        $model->setLimit($amount);
        $model->setOrder(['last_checked' => 'asc', $model->idField => 'asc']);
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
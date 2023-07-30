<?php

declare(strict_types=1);

namespace secondarymodelforatk;

use Atk4\Core\Exception;

/**
 * This class can be used to check, e.g. in cronjobs, if SecondaryModels without parent exist.
 * The field last_checked is used to indicate when the record was last checked.
 * In theory, a SecondaryModel without a parent should not exist.
 */
class ParentExistsChecker
{

    /**
     * @param SecondaryModel $model
     * @param int $amount
     * @return array<SecondaryModel>
     * @throws Exception
     * @throws \Atk4\Data\Exception
     */
    public function deleteSecondaryModelsWithoutParent(SecondaryModel $model, int $amount = 100): array
    {
        $deletedRecords = [];
        $model->setLimit($amount);
        $model->setOrder(['last_checked' => 'asc', $model->idField => 'asc']);
        foreach ($model as $entity) {
            try {
                $entity->getParentObject();
                $entity->set('last_checked', new \DateTime());
                $entity->save();
            } catch (ParentNotFoundException $e) {
                $deletedRecords[] = clone $entity;
                $entity->delete();
            }
        }

        return $deletedRecords;
    }
}
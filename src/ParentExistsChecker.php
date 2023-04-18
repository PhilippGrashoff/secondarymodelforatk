<?php

declare(strict_types=1);

namespace secondarymodelforatk;

use Atk4\Data\Exception;

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
            catch (\Throwable $e) {
                $ex = new Exception("Fehler in " . __FUNCTION__ . ': ' . $e->getMessage());
                $ex->addMoreInfo('id', $record->getId());
                $ex->addMoreInfo('model', get_class($record));
                throw $ex;
            }
        }

        return $deletedRecords;
    }
}
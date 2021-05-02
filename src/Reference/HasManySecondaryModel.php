<?php

declare(strict_types=1);

namespace secondarymodelforatk\Reference;

use Atk4\Data\Field;
use Atk4\Data\Reference\HasMany;

class HasManySecondaryModel extends HasMany {

    protected $our_model_class = '';

    public function getOurFieldName(): string {
        return $this->our_field;
    }

    public function getOurModelClass(): string {
        return $this->our_model_class;
    }
}
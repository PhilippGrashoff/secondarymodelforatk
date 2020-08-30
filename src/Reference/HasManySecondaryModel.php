<?php

declare(strict_types=1);

namespace secondarymodelforatk\Reference;

use atk4\data\Reference\HasMany;

class HasManySecondaryModel extends HasMany {

    protected $our_model_class = '';

    public function getOurField(): string {
        return $this->our_field;
    }

    public function getOurModelClass(): string {
        return $this->our_model_class;
    }
}
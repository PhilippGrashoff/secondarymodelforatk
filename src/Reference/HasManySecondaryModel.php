<?php

declare(strict_types=1);

namespace PhilippR\Atk4\SecondaryModel\Reference;

use Atk4\Data\Reference\HasMany;

class HasManySecondaryModel extends HasMany
{

    protected string $ourModelClass = '';

    public function getOurFieldName(): string
    {
        return $this->ourField;
    }

    public function getOurModelClass(): string
    {
        return $this->ourModelClass;
    }
}
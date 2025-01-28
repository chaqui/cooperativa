<?php

namespace App\Services;

use App\Models\Reference;
use Illuminate\Database\Eloquent\Collection;

class ReferenceService
{

    public function getReference(string $id): Reference
    {
        return Reference::findOrFail($id);
    }

    public function getReferences(): Collection
    {
        return Reference::all();
    }

    public function createReference(array $referenceData): Reference
    {
        return Reference::create($referenceData);
    }

    public function updateReference(Reference $reference, array $referenceData): Reference
    {
        $reference->update($referenceData);
        return $reference;
    }
}

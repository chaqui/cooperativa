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

    public function updateReference( $id, array $referenceData): Reference
    {
        $reference = $this->getReference($id);
        $reference->update($referenceData);
        return $reference;
    }
}

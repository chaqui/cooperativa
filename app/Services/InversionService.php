<?php

namespace App\Services;

use Illuminate\Support\Collection;

use App\Models\Inversion;
class InversionService{

    public function getInversion(string $id): Inversion
    {
        return Inversion::findOrFail($id);
    }

    public function getInversiones(): Collection
    {
        return Inversion::all();
    }

    public function createInversion(array $inversionData): Inversion
    {
        return Inversion::create($inversionData);
    }

    public function updateInversion(Inversion $inversion, array $inversionData): Inversion
    {
        $inversion->update($inversionData);
        return $inversion;
    }

    public function deleteInversion(Inversion $inversion): void
    {
        $inversion->delete();
    }

}

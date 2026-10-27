<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case MedicalForm = 'medical_form';
    case Prescription = 'prescription';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MedicalForm => 'Fiche maladie',
            self::Prescription => 'Ordonnance',
            self::Other => 'Autre document',
        };
    }
}

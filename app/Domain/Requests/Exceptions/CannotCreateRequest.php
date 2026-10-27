<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

use DomainException;

/** A request cannot be created: the reason is meant to be shown to the facility agent. */
final class CannotCreateRequest extends DomainException
{
    public static function unknownBeneficiary(): self
    {
        return new self('Aucun bénéficiaire ne correspond à cette sélection pour l’assureur choisi.');
    }

    public static function insurerNotPartnered(): self
    {
        return new self('Votre établissement ne travaille pas avec cet assureur.');
    }

    public static function invalidCoverageSelection(): self
    {
        return new self('La couverture sélectionnée n’est plus valide. Recherchez à nouveau le bénéficiaire.');
    }

    public static function missingTariff(string $actLabel): self
    {
        return new self("Aucun tarif officiel n'est défini pour l'acte « {$actLabel} ».");
    }

    public static function invalidRelatedRequest(): self
    {
        return new self('La demande liée doit être une demande approuvée de votre établissement pour le même bénéficiaire.');
    }
}

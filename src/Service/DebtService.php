<?php

namespace App\Service;

use App\Core\Database;
use App\Model\Entity\Dette;
use App\Model\Entity\ModePaiement;
use App\Model\Entity\Paiement;
use App\Model\Entity\User;
use App\Model\Repository\ClientRepository;
use App\Model\Repository\DetteRepository;
use DateTime;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class DebtService
{
    public static function enregistrerRemboursement(
        int $detteId,
        float $montant,
        int $modePaiementId,
        int $userId = 1,
        ?string $reference = null
    ): array {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant du remboursement doit être strictement supérieur à zéro.");
        }

        $dette = DetteRepository::findById($detteId);
        if (!$dette) {
            throw new InvalidArgumentException("La dette #DT-{$detteId} est introuvable.");
        }

        if ($dette->estSoldee() || $dette->getMontantRestant() <= 0) {
            throw new RuntimeException("Cette dette est déjà intégralement soldée.");
        }

        if ($montant > $dette->getMontantRestant()) {
            throw new InvalidArgumentException(
                sprintf(
                    "Le montant du versement (%s FCFA) ne peut pas excéder le reste dû (%s FCFA).",
                    number_format($montant, 0, ',', ' '),
                    number_format($dette->getMontantRestant(), 0, ',', ' ')
                )
            );
        }

        $pdo = Database::getPDO();
        $pdo->beginTransaction();

        try {
            $paiement = new Paiement(
                dette: $dette,
                montant: $montant,
                datePaiement: new DateTime(),
                modePaiement: new ModePaiement(id: $modePaiementId),
                referencePaiement: $reference,
                agent: new User(id: $userId)
            );

            DetteRepository::savePaiement($paiement);

            $nouveauReste = max(0.0, round($dette->getMontantRestant() - $montant, 2));

            if ($nouveauReste <= 0.0) {
                $statutId = 2;
            } elseif ($dette->estEnRetard()) {
                $statutId = 3;
            } else {
                $statutId = 1;
            }

            DetteRepository::updateMontantRestantEtStatut($detteId, $nouveauReste, $statutId);

            if ($dette->getClient() !== null && $dette->getClient()->getId() !== null) {
                ClientRepository::diminuerDette($dette->getClient()->getId(), $montant);
            }

            $pdo->commit();

            $estSoldee = ($nouveauReste <= 0.0);
            $message = $estSoldee
                ? "Dette #DT-{$detteId} intégralement soldée avec succès !"
                : "Versement de " . number_format($montant, 0, ',', ' ') . " FCFA enregistré. Reste dû : " . number_format($nouveauReste, 0, ',', ' ') . " FCFA.";

            return [
                'success' => true,
                'dette_id' => $detteId,
                'paiement_id' => $paiement->getId(),
                'montant_verse' => $montant,
                'nouveau_reste' => $nouveauReste,
                'est_soldee' => $estSoldee,
                'statut_id' => $statutId,
                'client_id' => $dette->getClient()?->getId(),
                'message' => $message
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function soldeTotalDette(
        int $detteId,
        int $modePaiementId,
        int $userId = 1,
        ?string $reference = null
    ): array {
        $dette = DetteRepository::findById($detteId);
        if (!$dette) {
            throw new InvalidArgumentException("La dette #DT-{$detteId} est introuvable.");
        }

        return self::enregistrerRemboursement(
            $detteId,
            $dette->getMontantRestant(),
            $modePaiementId,
            $userId,
            $reference
        );
    }

    public static function getDette(int $id): ?Dette
    {
        return DetteRepository::findById($id);
    }

    public static function getDettesActives(): array
    {
        return DetteRepository::findDettesActives();
    }

    public static function getAllDettes(): array
    {
        return DetteRepository::findAll();
    }

    public static function getDettesClient(int $clientId): array
    {
        return DetteRepository::findByClient($clientId);
    }

    public static function getHistoriquePaiements(int $detteId): array
    {
        return DetteRepository::findPaiementsByDetteId($detteId);
    }

    public static function getStatistiquesDettes(): array
    {
        $totalEncours = DetteRepository::getTotalEncours();
        $totalRecouvrements = DetteRepository::getTotalRecouvrements();
        $totalCreancesInitiales = DetteRepository::getTotalCreancesInitiales();
        $nombreActives = DetteRepository::countActives();
        $nombreSoldees = DetteRepository::countSoldees();
        $nombreTotal = DetteRepository::count();

        $tauxRecouvrement = $totalCreancesInitiales > 0
            ? round(($totalRecouvrements / $totalCreancesInitiales) * 100, 1)
            : 0.0;

        return [
            'total_encours' => $totalEncours,
            'total_recouvrements' => $totalRecouvrements,
            'total_creances_initiales' => $totalCreancesInitiales,
            'nombre_actives' => $nombreActives,
            'nombre_soldees' => $nombreSoldees,
            'nombre_total' => $nombreTotal,
            'taux_recouvrement' => $tauxRecouvrement
        ];
    }
}

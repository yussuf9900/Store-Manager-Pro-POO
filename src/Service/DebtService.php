<?php

namespace App\Service;

use App\Core\Database;
use App\Model\Entity\Dette;
use App\Model\Entity\Paiement;
use App\Model\Repository\ClientRepository;
use App\Model\Repository\DetteRepository;
use DateTime;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class DebtService
{
    private PDO $pdo;
    private DetteRepository $detteRepository;
    private ClientRepository $clientRepository;

    public function __construct(
        ?PDO $pdo = null,
        ?DetteRepository $detteRepository = null,
        ?ClientRepository $clientRepository = null
    ) {
        $this->pdo = $pdo ?? Database::getPDO();
        $this->detteRepository = $detteRepository ?? new DetteRepository($this->pdo);
        $this->clientRepository = $clientRepository ?? new ClientRepository($this->pdo);
    }

    public function enregistrerRemboursement(
        int $detteId,
        float $montant,
        int $modePaiementId,
        int $userId = 1,
        ?string $reference = null
    ): array {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant du remboursement doit être strictement supérieur à zéro.");
        }

        $dette = $this->detteRepository->findById($detteId);
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

        $this->pdo->beginTransaction();

        try {
            $paiement = new Paiement(
                detteId: $detteId,
                montant: $montant,
                datePaiement: new DateTime(),
                modePaiementId: $modePaiementId,
                referencePaiement: $reference,
                userId: $userId
            );

            $this->detteRepository->savePaiement($paiement);

            $nouveauReste = max(0.0, round($dette->getMontantRestant() - $montant, 2));

            if ($nouveauReste <= 0.0) {
                $statutId = 2;
            } elseif ($dette->estEnRetard()) {
                $statutId = 3;
            } else {
                $statutId = 1;
            }

            $this->detteRepository->updateMontantRestantEtStatut($detteId, $nouveauReste, $statutId);

            $this->clientRepository->diminuerDette($dette->getClientId(), $montant);

            $this->pdo->commit();

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
                'client_id' => $dette->getClientId(),
                'message' => $message
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function soldeTotalDette(
        int $detteId,
        int $modePaiementId,
        int $userId = 1,
        ?string $reference = null
    ): array {
        $dette = $this->detteRepository->findById($detteId);
        if (!$dette) {
            throw new InvalidArgumentException("La dette #DT-{$detteId} est introuvable.");
        }

        return $this->enregistrerRemboursement(
            $detteId,
            $dette->getMontantRestant(),
            $modePaiementId,
            $userId,
            $reference
        );
    }

    public function getDette(int $id): ?Dette
    {
        return $this->detteRepository->findById($id);
    }

    public function getDettesActives(): array
    {
        return $this->detteRepository->findDettesActives();
    }

    public function getAllDettes(): array
    {
        return $this->detteRepository->findAll();
    }

    public function getDettesClient(int $clientId): array
    {
        return $this->detteRepository->findByClient($clientId);
    }

    public function getHistoriquePaiements(int $detteId): array
    {
        return $this->detteRepository->findPaiementsByDetteId($detteId);
    }

    public function getStatistiquesDettes(): array
    {
        $totalEncours = $this->detteRepository->getTotalEncours();
        $totalRecouvrements = $this->detteRepository->getTotalRecouvrements();
        $totalCreancesInitiales = $this->detteRepository->getTotalCreancesInitiales();
        $nombreActives = $this->detteRepository->countActives();
        $nombreSoldees = $this->detteRepository->countSoldees();
        $nombreTotal = $this->detteRepository->count();

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

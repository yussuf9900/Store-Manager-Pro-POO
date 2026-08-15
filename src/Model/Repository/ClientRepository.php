<?php

namespace App\Model\Repository;

use App\Model\Entity\Client;
use DateTime;
use PDO;

class ClientRepository extends AbstractRepository
{
    public function findById(int $id): ?Client
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByTelephone(string $telephone): ?Client
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE telephone = :tel LIMIT 1");
        $stmt->bindValue(':tel', trim($telephone), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM clients ORDER BY nom ASC, prenom ASC");
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function search(string $term): array
    {
        $cleanTerm = '%' . trim(strtolower($term)) . '%';
        $stmt = $this->pdo->prepare(
            "SELECT * FROM clients 
             WHERE LOWER(nom) LIKE :term 
                OR LOWER(prenom) LIKE :term 
                OR LOWER(telephone) LIKE :term 
                OR LOWER(COALESCE(email, '')) LIKE :term 
             ORDER BY nom ASC, prenom ASC"
        );
        $stmt->bindValue(':term', $cleanTerm, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findClientsAvecDettes(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM clients WHERE total_dettes_actuelles > 0 ORDER BY total_dettes_actuelles DESC, nom ASC"
        );
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findSolvablesPourCredit(float $montant): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM clients WHERE (limite_credit - total_dettes_actuelles) >= :montant ORDER BY nom ASC, prenom ASC"
        );
        $stmt->bindValue(':montant', $montant);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Client $client): bool
    {
        if ($client->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO clients (nom, prenom, telephone, email, adresse, limite_credit, total_dettes_actuelles) 
                 VALUES (:nom, :prenom, :telephone, :email, :adresse, :limite_credit, :total_dettes_actuelles)"
            );
            $stmt->bindValue(':nom', $client->getNom(), PDO::PARAM_STR);
            $stmt->bindValue(':prenom', $client->getPrenom(), PDO::PARAM_STR);
            $stmt->bindValue(':telephone', $client->getTelephone(), PDO::PARAM_STR);
            $stmt->bindValue(':email', $client->getEmail(), PDO::PARAM_STR);
            $stmt->bindValue(':adresse', $client->getAdresse(), PDO::PARAM_STR);
            $stmt->bindValue(':limite_credit', $client->getLimiteCredit());
            $stmt->bindValue(':total_dettes_actuelles', $client->getTotalDettesActuelles());

            $result = $stmt->execute();
            if ($result) {
                $client->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE clients SET 
                nom = :nom, 
                prenom = :prenom, 
                telephone = :telephone, 
                email = :email, 
                adresse = :adresse, 
                limite_credit = :limite_credit, 
                total_dettes_actuelles = :total_dettes_actuelles 
             WHERE id = :id"
        );
        $stmt->bindValue(':nom', $client->getNom(), PDO::PARAM_STR);
        $stmt->bindValue(':prenom', $client->getPrenom(), PDO::PARAM_STR);
        $stmt->bindValue(':telephone', $client->getTelephone(), PDO::PARAM_STR);
        $stmt->bindValue(':email', $client->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(':adresse', $client->getAdresse(), PDO::PARAM_STR);
        $stmt->bindValue(':limite_credit', $client->getLimiteCredit());
        $stmt->bindValue(':total_dettes_actuelles', $client->getTotalDettesActuelles());
        $stmt->bindValue(':id', $client->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateTotalDettes(int $id, float $montant): bool
    {
        $stmt = $this->pdo->prepare("UPDATE clients SET total_dettes_actuelles = :dette WHERE id = :id AND :dette >= 0");
        $stmt->bindValue(':dette', max(0.0, $montant));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function ajouterDette(int $id, float $montant): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE clients 
             SET total_dettes_actuelles = total_dettes_actuelles + :montant 
             WHERE id = :id AND (total_dettes_actuelles + :montant) <= limite_credit"
        );
        $stmt->bindValue(':montant', $montant);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function diminuerDette(int $id, float $montant): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE clients 
             SET total_dettes_actuelles = CASE 
                WHEN total_dettes_actuelles >= :montant THEN total_dettes_actuelles - :montant 
                ELSE 0.00 
             END 
             WHERE id = :id"
        );
        $stmt->bindValue(':montant', $montant);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM clients");
        return (int)$stmt->fetchColumn();
    }

    public function getTotalCreances(): float
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(total_dettes_actuelles), 0) FROM clients");
        return (float)$stmt->fetchColumn();
    }

    protected function hydrate(array $row): Client
    {
        $dateCreation = null;
        if (!empty($row['created_at'])) {
            $dateCreation = new DateTime($row['created_at']);
        }

        return new Client(
            id: isset($row['id']) ? (int)$row['id'] : null,
            nom: $row['nom'] ?? '',
            prenom: $row['prenom'] ?? '',
            telephone: $row['telephone'] ?? '',
            email: $row['email'] ?? null,
            adresse: $row['adresse'] ?? null,
            limiteCredit: isset($row['limite_credit']) ? (float)$row['limite_credit'] : 0.0,
            totalDettesActuelles: isset($row['total_dettes_actuelles']) ? (float)$row['total_dettes_actuelles'] : 0.0,
            dateCreation: $dateCreation
        );
    }
}

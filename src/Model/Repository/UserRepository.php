<?php

namespace App\Model\Repository;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use DateTime;
use PDO;

class UserRepository extends AbstractRepository
{
    private string $baseSelect = "SELECT u.*, 
            r.id AS role_id_ref, r.code AS role_code, r.libelle AS role_libelle, r.description AS role_description 
        FROM utilisateurs u 
        LEFT JOIN roles r ON u.role_id = r.id";

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE u.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $cleanEmail = strtolower(trim($email));
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE LOWER(u.email) = :email LIMIT 1");
        $stmt->bindValue(':email', $cleanEmail, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row && (str_ends_with($cleanEmail, '@storemanager.sn') || str_ends_with($cleanEmail, '@storemanager.pro'))) {
            $altDomain = str_ends_with($cleanEmail, '@storemanager.sn') 
                ? str_replace('@storemanager.sn', '@storemanager.pro', $cleanEmail)
                : str_replace('@storemanager.pro', '@storemanager.sn', $cleanEmail);
            $stmtAlt = $this->pdo->prepare("{$this->baseSelect} WHERE LOWER(u.email) = :email LIMIT 1");
            $stmtAlt->bindValue(':email', $altDomain, PDO::PARAM_STR);
            $stmtAlt->execute();
            $row = $stmtAlt->fetch();
        }

        return $row ? $this->hydrate($row) : null;
    }

    public function findByRole(int $roleId): array
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE u.role_id = :role_id ORDER BY u.nom ASC, u.prenom ASC");
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByRoleCode(string $code): ?User
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE UPPER(r.code) = :code AND u.actif = true ORDER BY u.id ASC LIMIT 1");
        $stmt->bindValue(':code', strtoupper(trim($code)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("{$this->baseSelect} ORDER BY u.nom ASC, u.prenom ASC");
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(User $user): bool
    {
        if ($user->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, actif, created_at)
                 VALUES (:nom, :prenom, :email, :mot_de_passe, :role_id, :actif, :created_at)"
            );
            $stmt->bindValue(':nom', $user->getNom(), PDO::PARAM_STR);
            $stmt->bindValue(':prenom', $user->getPrenom(), PDO::PARAM_STR);
            $stmt->bindValue(':email', $user->getEmail(), PDO::PARAM_STR);
            $stmt->bindValue(':mot_de_passe', $user->getMotDePasse(), PDO::PARAM_STR);
            $stmt->bindValue(':role_id', $user->getRoleId(), PDO::PARAM_INT);
            $stmt->bindValue(':actif', $user->isActif(), PDO::PARAM_BOOL);
            $stmt->bindValue(':created_at', $user->getDateCreation() ? $user->getDateCreation()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'), PDO::PARAM_STR);

            $result = $stmt->execute();
            if ($result) {
                $user->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs SET
                nom = :nom,
                prenom = :prenom,
                email = :email,
                mot_de_passe = :mot_de_passe,
                role_id = :role_id,
                actif = :actif
             WHERE id = :id"
        );
        $stmt->bindValue(':nom', $user->getNom(), PDO::PARAM_STR);
        $stmt->bindValue(':prenom', $user->getPrenom(), PDO::PARAM_STR);
        $stmt->bindValue(':email', $user->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(':mot_de_passe', $user->getMotDePasse(), PDO::PARAM_STR);
        $stmt->bindValue(':role_id', $user->getRoleId(), PDO::PARAM_INT);
        $stmt->bindValue(':actif', $user->isActif(), PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $user->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM utilisateurs");
        return (int)$stmt->fetchColumn();
    }

    protected function hydrate(array $row): User
    {
        $role = null;
        if (!empty($row['role_id']) || !empty($row['role_code'])) {
            $role = new Role(
                id: isset($row['role_id_ref']) ? (int)$row['role_id_ref'] : (isset($row['role_id']) ? (int)$row['role_id'] : null),
                code: $row['role_code'] ?? '',
                libelle: $row['role_libelle'] ?? '',
                description: $row['role_description'] ?? null
            );
        }

        $dateCreation = !empty($row['created_at']) ? new DateTime($row['created_at']) : null;
        $actif = isset($row['actif']) ? (bool)$row['actif'] : true;

        return new User(
            id: isset($row['id']) ? (int)$row['id'] : null,
            nom: $row['nom'] ?? '',
            prenom: $row['prenom'] ?? '',
            email: $row['email'] ?? '',
            motDePasse: $row['mot_de_passe'] ?? '',
            roleId: isset($row['role_id']) ? (int)$row['role_id'] : 0,
            role: $role,
            actif: $actif,
            dateCreation: $dateCreation
        );
    }
}

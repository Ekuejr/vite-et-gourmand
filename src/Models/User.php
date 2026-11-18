<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Accès aux utilisateurs (clients, employés, administrateur).
 */
final class User extends Model
{
    private const SELECT = 'SELECT u.*, r.libelle AS role FROM utilisateur u JOIN role r ON r.role_id = u.role_id';

    public function find(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE u.utilisateur_id = :id', ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE u.email = :email', ['email' => mb_strtolower($email)]);
    }

    /**
     * Création d'un compte. Le rôle est fixé par le code appelant, jamais par le formulaire :
     * impossible de s'auto-attribuer le rôle administrateur.
     */
    public function create(array $d, string $role): int
    {
        $this->execute(
            'INSERT INTO utilisateur (role_id, email, password, nom, prenom, telephone, adresse_postale, code_postal, ville)
             VALUES ((SELECT role_id FROM role WHERE libelle = :role), :email, :password, :nom, :prenom, :tel, :adresse, :cp, :ville)',
            [
                'role'     => $role,
                'email'    => mb_strtolower($d['email']),
                'password' => password_hash($d['password'], PASSWORD_DEFAULT),
                'nom'      => $d['nom'],
                'prenom'   => $d['prenom'],
                'tel'      => $d['telephone'] ?? null,
                'adresse'  => $d['adresse_postale'] ?? null,
                'cp'       => $d['code_postal'] ?? null,
                'ville'    => $d['ville'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, array $d): void
    {
        $this->execute(
            'UPDATE utilisateur SET nom = :nom, prenom = :prenom, telephone = :tel, adresse_postale = :adresse,
                    code_postal = :cp, ville = :ville WHERE utilisateur_id = :id',
            ['nom' => $d['nom'], 'prenom' => $d['prenom'], 'tel' => $d['telephone'], 'adresse' => $d['adresse_postale'],
             'cp' => $d['code_postal'], 'ville' => $d['ville'], 'id' => $id]
        );
    }

    public function updatePassword(int $id, string $plainPassword): void
    {
        $this->execute('UPDATE utilisateur SET password = :p WHERE utilisateur_id = :id', [
            'p' => password_hash($plainPassword, PASSWORD_DEFAULT), 'id' => $id,
        ]);
    }

    public function employees(): array
    {
        return $this->fetchAll(self::SELECT . " WHERE r.libelle = 'employe' ORDER BY u.nom, u.prenom");
    }

    /** Désactivation / réactivation d'un compte EMPLOYÉ uniquement. */
    public function setEmployeeActive(int $id, bool $active): bool
    {
        return $this->execute(
            "UPDATE utilisateur u JOIN role r ON r.role_id = u.role_id SET u.actif = :a
             WHERE u.utilisateur_id = :id AND r.libelle = 'employe'",
            ['a' => $active ? 1 : 0, 'id' => $id]
        ) > 0;
    }

    public function clients(): array
    {
        return $this->fetchAll(self::SELECT . " WHERE r.libelle = 'utilisateur' ORDER BY u.nom, u.prenom");
    }
}

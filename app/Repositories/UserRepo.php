<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

/**
 * Email+password is the only way into any account, patient/nutritionist/
 * admin alike — role lives on the user row, not the login mechanism.
 * Registration is explicit for both roles (RegisterController,
 * NutriRegisterController) rather than the old find-or-create-on-verify
 * dance, so this repo only ever creates a genuinely new row.
 */
final class UserRepo
{
    public function find(int $id): ?array
    {
        return Db::first('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return Db::first('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
    }

    public function createPatient(string $email, string $passwordHash): int
    {
        return Db::insert(
            "INSERT INTO users (role, email, password_hash) VALUES ('patient', ?, ?)",
            [$email, $passwordHash]
        );
    }

    /** Lands in nutritionist_status='pending' — approval is never self-service. */
    public function createNutritionist(string $email, string $passwordHash, string $credentials): int
    {
        return Db::insert(
            "INSERT INTO users (role, email, password_hash, nutritionist_status, nutritionist_creds)
             VALUES ('nutritionist', ?, ?, 'pending', ?)",
            [$email, $passwordHash, $credentials]
        );
    }
}

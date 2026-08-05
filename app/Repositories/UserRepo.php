<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Crypto;
use App\Core\Db;

/**
 * The mobile number is stored encrypted (see the addendum migration) so a
 * lookup by number has to go through the blind-index hash, never a
 * plaintext WHERE mobile = ?. Every row this returns gets a decrypted
 * `mobile_plain` added alongside the raw ciphertext, so callers never have
 * to remember to decrypt it themselves.
 */
final class UserRepo
{
    public function find(int $id): ?array
    {
        return $this->withPlainMobile(
            Db::first('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id])
        );
    }

    public function findByMobile(string $mobile): ?array
    {
        return $this->withPlainMobile(Db::first(
            'SELECT * FROM users WHERE mobile_hash = ? AND deleted_at IS NULL',
            [Crypto::blindIndex('mobile:' . $mobile)]
        ));
    }

    public function findByEmail(string $email): ?array
    {
        return $this->withPlainMobile(
            Db::first('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$email])
        );
    }

    /** Find the patient by mobile, or create one. Returns [id, isNew]. */
    public function findOrCreatePatient(string $mobile, string $operator): array
    {
        $existing = $this->findByMobile($mobile);
        if ($existing !== null) {
            return [(int) $existing['id'], false];
        }

        $id = Db::insert(
            'INSERT INTO users (role, mobile, mobile_hash, operator) VALUES (?, ?, ?, ?)',
            ['patient', Crypto::encrypt($mobile), Crypto::blindIndex('mobile:' . $mobile), $operator]
        );

        return [$id, true];
    }

    public function createStaff(string $email, string $passwordHash, string $role, ?string $nutritionistCreds = null): int
    {
        return Db::insert(
            'INSERT INTO users (role, email, password_hash, nutritionist_status, nutritionist_creds)
             VALUES (?, ?, ?, ?, ?)',
            [$role, $email, $passwordHash, $role === 'nutritionist' ? 'pending' : null, $nutritionistCreds]
        );
    }

    private function withPlainMobile(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }
        if (isset($row['mobile']) && $row['mobile'] !== null) {
            $row['mobile_plain'] = Crypto::decrypt((string) $row['mobile']);
        }
        return $row;
    }
}

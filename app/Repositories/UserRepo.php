<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Crypto;
use App\Core\Db;

/**
 * Mobile+OTP is the only way into any account, patient/nutritionist/admin
 * alike — role lives on the user row, not the login mechanism. The mobile
 * number is stored encrypted, so a lookup by number has to go through the
 * blind-index hash, never a plaintext WHERE mobile = ?. Every row this
 * returns gets a decrypted `mobile_plain` added alongside the raw
 * ciphertext, so callers never have to remember to decrypt it themselves.
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

    /**
     * Used by the plain subscribe flow. Only ever creates a *new* number as
     * a patient — an existing number keeps whatever role it already has, so
     * a nutritionist or admin logging in through the same OTP screen isn't
     * touched. Returns [id, isNew].
     */
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

    /**
     * Used by nutritionist registration. A brand-new number becomes a
     * pending nutritionist outright. An existing number gets upgraded to
     * nutritionist (covers someone who signed up as a patient first and
     * later decides to register as one) — but an already-approved account
     * never gets silently reset back to pending just for resubmitting this
     * form. Returns [id, isNew].
     */
    public function findOrCreateNutritionist(string $mobile, string $operator, string $credentials): array
    {
        $existing = $this->findByMobile($mobile);

        if ($existing !== null) {
            $status = in_array($existing['nutritionist_status'], ['approved', 'pending'], true)
                ? $existing['nutritionist_status']
                : 'pending';

            Db::exec(
                'UPDATE users SET role = ?, nutritionist_status = ?, nutritionist_creds = ? WHERE id = ?',
                ['nutritionist', $status, $credentials, $existing['id']]
            );

            return [(int) $existing['id'], false];
        }

        $id = Db::insert(
            'INSERT INTO users (role, mobile, mobile_hash, operator, nutritionist_status, nutritionist_creds)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                'nutritionist',
                Crypto::encrypt($mobile),
                Crypto::blindIndex('mobile:' . $mobile),
                $operator,
                'pending',
                $credentials,
            ]
        );

        return [$id, true];
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

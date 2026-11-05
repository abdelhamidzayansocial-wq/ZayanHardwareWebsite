<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    /** True when an account identifier is already registered. */
    public static function exists(string $field, string $value): bool
    {
        if (!in_array($field, ['username', 'email', 'phone'], true)) {
            throw new \InvalidArgumentException('Invalid field.');
        }

        $stmt = self::db()->prepare('SELECT 1 FROM users WHERE ' . $field . ' = ? LIMIT 1');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() !== false;
    }

    /** Returns usernames already taken from one bounded IN query. */
    public static function existingUsernames(array $usernames): array
    {
        $usernames = array_values(array_unique(array_filter($usernames, 'is_string')));
        if (!$usernames) return [];
        $placeholders = implode(',', array_fill(0, count($usernames), '?'));
        $stmt = self::db()->prepare('SELECT username FROM users WHERE username IN (' . $placeholders . ')');
        $stmt->execute($usernames);
        return array_column($stmt->fetchAll(), 'username');
    }

    /** Login lookup in exactly one identifier column, selected by the caller. */
    public static function findForLogin(string $identifier, string $type = 'username'): ?array
    {
        if (!in_array($type, ['username', 'email', 'phone'], true)) {
            throw new \InvalidArgumentException('Invalid login identifier type.');
        }

        $stmt = self::db()->prepare(
            'SELECT id, username, password_hash, account_status, auth_version
             FROM users
             WHERE ' . $type . ' = ?
             LIMIT 1'
        );
        $stmt->execute([$identifier]);
        return $stmt->fetch() ?: null;
    }

    /** Safe columns only: never returns the password hash. */
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, username, display_name, email, role, account_status, created_at,
                   avatar_key, avatar_path, accept_help_requests
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** Returns the internal authentication version without exposing it to view data. */
    public static function authVersion(int $id): ?int
    {
        $stmt = self::db()->prepare('SELECT auth_version FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    public static function findProfileByUsername(string $username): ?array
    {
        $sql = 'SELECT u.id, u.username, u.display_name, u.role, u.account_status, u.created_at,
                       u.avatar_key, u.avatar_path, u.accept_help_requests,
                       p.device_focus,
                       (SELECT GROUP_CONCAT(uds.device_type ORDER BY uds.device_type SEPARATOR \',\')
                        FROM user_device_specializations uds WHERE uds.user_id = u.id) AS device_specializations,
                       p.tech_level, p.board_experience, p.level_verified, p.workshop_name, p.biography
                FROM users u
                LEFT JOIN technician_profiles p ON p.user_id = u.id
                WHERE u.username = ? LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public static function skillRatings(int $userId): array
    {
        $stmt = self::db()->prepare(
            'SELECT d.name_ar AS skill_name, r.rating, r.approved_assessments
             FROM user_skill_ratings r
             JOIN skill_domains d ON d.id = r.skill_domain_id
             WHERE r.user_id = ?
             ORDER BY d.sort_order, d.name_ar'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Save registration-stage profile data using the normalized schema created
     * by the registration and skills migrations. Assessment-backed ratings are
     * historical evidence and are never deleted when self-selected skills change.
     */
    public static function saveRegistrationStage2(int $userId, string $username, string $specialization, ?string $experience, array $devices): void
    {
        $pdo = self::db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$username, $userId]);

            $validDevices = array_values(array_unique(array_filter(
                $devices,
                static fn ($device): bool => is_string($device) && in_array($device, ['iphone', 'android'], true)
            )));
            $deviceFocus = $validDevices[0] ?? null;
            $validExperience = in_array($experience, ['lt1', '1-3', '3-5', '5-10', 'gt10'], true)
                ? $experience : null;

            $pdo->prepare(
                'UPDATE technician_profiles SET device_focus = ?, board_experience = ? WHERE user_id = ?'
            )->execute([$deviceFocus, $validExperience, $userId]);

            $pdo->prepare('DELETE FROM user_device_specializations WHERE user_id = ?')->execute([$userId]);
            $deviceStmt = $pdo->prepare('INSERT INTO user_device_specializations (user_id, device_type) VALUES (?, ?)');
            $devices = $profile['device_focuses'] ?? [];
            $allowedDevices = ['smartphone', 'laptop', 'desktop', 'screens', 'drone', 'playstation', 'xbox', 'home_appliances', 'networking', 'smartwatch', 'printers', 'other'];
            foreach (array_unique($devices) as $device) {
                if (is_string($device) && in_array($device, $allowedDevices, true)) {
                    $deviceStmt->execute([$id, $device]);
                }
            }

            $interestStmt = $pdo->prepare(
                'INSERT IGNORE INTO user_interest_domains (user_id, skill_domain_id)
                 SELECT ?, id FROM skill_domains WHERE slug = ? AND is_active = 1'
            );
            foreach (array_unique($profile['interest_domains'] ?? []) as $interest) {
                if (is_string($interest)) $interestStmt->execute([$id, $interest]);
            }

            $pdo->prepare(
                'INSERT INTO terms_acceptances (user_id, terms_version, ip_hash) VALUES (?, ?, ?)'
            )->execute([
                $id,
                (string) ($account['terms_version'] ?? ''),
                $account['terms_ip_hash'] ?? null,
            ]);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}

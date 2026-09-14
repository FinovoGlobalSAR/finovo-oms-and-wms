<?php
require_once __DIR__ . '/../../core/Database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ---------- Employees list/CRUD ----------

    public function allByCompany(): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.role_id, u.name, u.email, u.status, u.created_at, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            ORDER BY u.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdAndCompany(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.role_id, u.name, u.email, u.status, u.created_at, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
        }
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, name, email, password_hash, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['role_id'],
            $data['name'],
            strtolower(trim($data['email'])),
            $data['password_hash'],
            $data['status'],
        ]);
    }

    public function updateEmployee(int $id, array $data): bool
    {
        if (!empty($data['password_hash'])) {
            $stmt = $this->db->prepare("
                UPDATE users SET role_id = ?, name = ?, email = ?, password_hash = ?, status = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['role_id'], $data['name'], strtolower(trim($data['email'])),
                $data['password_hash'], $data['status'], $id,
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE users SET role_id = ?, name = ?, email = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([$data['role_id'], $data['name'], strtolower(trim($data['email'])), $data['status'], $id]);
    }

    public function deleteEmployee(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ---------- Login ----------

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.role_id, u.name, u.email, u.password_hash, u.status, r.name AS role
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE LOWER(u.email) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findValidLoggedInUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.role_id, u.name, u.email, u.status, r.name AS role
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.id = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    // ---------- Forgot Password ----------

    public function saveResetOtp(int $userId, string $otp, string $expiresAt): void
    {
        $stmt = $this->db->prepare("UPDATE users SET reset_otp = ?, reset_otp_expires = ? WHERE id = ?");
        $stmt->execute([$otp, $expiresAt, $userId]);
    }

    public function verifyResetOtp(int $userId, string $otp): bool
    {
        $stmt = $this->db->prepare("SELECT reset_otp, reset_otp_expires FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['reset_otp']) || empty($row['reset_otp_expires'])) {
            return false;
        }

        $enteredOtp = trim($otp);
        $storedOtp = trim($row['reset_otp']);

        if (!hash_equals($storedOtp, $enteredOtp)) {
            return false;
        }

        $expiresTimestamp = strtotime($row['reset_otp_expires']);
        return $expiresTimestamp !== false && $expiresTimestamp >= time();
    }

    public function clearResetOtp(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET reset_otp = NULL, reset_otp_expires = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function resetPassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = ?, reset_otp = NULL, reset_otp_expires = NULL WHERE id = ?"
        );
        $stmt->execute([$passwordHash, $userId]);
    }
}
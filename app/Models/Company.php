<?php
// app/Models/Company.php
require_once __DIR__ . '/../../core/Model.php';

class Company extends Model
{
    public function findById(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM companies WHERE id = ?", [$id]);
        $company = $stmt->fetch();
        return $company ?: null;
    }
}
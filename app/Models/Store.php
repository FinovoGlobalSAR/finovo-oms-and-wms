<?php
// app/Models/Store.php
require_once __DIR__ . '/../../core/Model.php';

class Store extends Model
{
    public function first(): ?array
    {
        $stmt = $this->query("SELECT * FROM stores ORDER BY id ASC LIMIT 1");
        $store = $stmt->fetch();
        return $store ?: null;
    }
}
<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM shipments")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('handover_status', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN handover_status ENUM('pending', 'handed_over') NOT NULL DEFAULT 'pending'");
        }
        if (!in_array('handover_scanned_at', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN handover_scanned_at TIMESTAMP NULL");
        }
        if (!in_array('handover_scanned_by', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN handover_scanned_by VARCHAR(100) NULL");
        }
        if (!in_array('cod_amount', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN cod_amount DECIMAL(12,2) NOT NULL DEFAULT 0");
        }
        if (!in_array('remittance_status', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN remittance_status ENUM('not_remitted', 'remitted') NOT NULL DEFAULT 'not_remitted'");
        }
        if (!in_array('remittance_amount', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN remittance_amount DECIMAL(12,2) NULL");
        }
        if (!in_array('remitted_at', $columns)) {
            $db->exec("ALTER TABLE shipments ADD COLUMN remitted_at TIMESTAMP NULL");
        }
    },
    'down' => function (PDO $db) {
        foreach (['handover_status', 'handover_scanned_at', 'handover_scanned_by', 'cod_amount', 'remittance_status', 'remittance_amount', 'remitted_at'] as $col) {
            $columns = $db->query("SHOW COLUMNS FROM shipments")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array($col, $columns)) {
                $db->exec("ALTER TABLE shipments DROP COLUMN {$col}");
            }
        }
    },
];
<?php
/**
 * Login sirf in 4 roles ko allow karta hai (AuthController::login):
 *   Admin, Manager, Sales Staff, Warehouse Staff
 *
 * Pehle ye roles sirf local database mein haath se daale gaye the, is liye live
 * server pe `roles` table khaali thi aur koi login nahi kar sakta tha.
 *
 * Ye migration:
 *  1. Ye 4 roles bana deti hai agar pehle se na hon.
 *  2. Ek hi naam ke duplicate roles (jaise "Sales Staff" 3 baar, "Warehouse-staff")
 *     ko ek mein jor deti hai: un roles wale users ko pehle wale role pe shift karke
 *     baaki duplicate rows delete kar deti hai.
 *
 * "Company Admin" jaise purane roles ko nahi chhedti — unhe Admin banana hai ya nahi,
 * ye team lead decide karein (Employees page se user ka role badal sakte hain).
 */
return [
    'up' => function (PDO $db) {
        $defaultRoles = ['Admin', 'Manager', 'Sales Staff', 'Warehouse Staff'];

        // "Warehouse-staff" / " sales  staff " => "warehouse staff"
        $normalize = function (string $name): string {
            $name = strtolower(trim($name));
            $name = str_replace(['-', '_'], ' ', $name);
            return preg_replace('/\s+/', ' ', $name);
        };

        // 1) Default roles banao (agar maujood nahi)
        $existing = $db->query("SELECT id, name FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $existingNormalized = array_map(fn($r) => $normalize($r['name']), $existing);

        $insert = $db->prepare("INSERT INTO roles (name) VALUES (?)");
        foreach ($defaultRoles as $role) {
            if (!in_array($normalize($role), $existingNormalized, true)) {
                $insert->execute([$role]);
            }
        }

        // 2) Duplicate roles ko ek mein jor do
        $roles = $db->query("SELECT id, name FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $keepIdByName = [];
        $moveUsers = $db->prepare("UPDATE users SET role_id = ? WHERE role_id = ?");
        $deleteRole = $db->prepare("DELETE FROM roles WHERE id = ?");
        $renameRole = $db->prepare("UPDATE roles SET name = ? WHERE id = ?");

        foreach ($roles as $role) {
            $key = $normalize($role['name']);

            if (!isset($keepIdByName[$key])) {
                $keepIdByName[$key] = (int) $role['id'];

                // Default roles ka naam sahi likhawat mein kar do (e.g. "Warehouse-staff" => "Warehouse Staff")
                foreach ($defaultRoles as $default) {
                    if ($normalize($default) === $key && $role['name'] !== $default) {
                        $renameRole->execute([$default, (int) $role['id']]);
                    }
                }
                continue;
            }

            $moveUsers->execute([$keepIdByName[$key], (int) $role['id']]);
            $deleteRole->execute([(int) $role['id']]);
        }
    },

    'down' => function (PDO $db) {
        // Roles delete karne se users ka login toot jayega — is liye down kuch nahi karta.
    },
];
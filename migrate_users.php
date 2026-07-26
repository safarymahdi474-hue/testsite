<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $version = $conn->query("SELECT VERSION()")->fetchColumn();
    $databaseName = $conn->query("SELECT DATABASE()")->fetchColumn();

    if (!$databaseName) {
        throw new RuntimeException('No database selected.');
    }

    $tableExistsStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = :schema_name
          AND TABLE_NAME = 'users'
    ");
    $tableExistsStmt->execute([':schema_name' => $databaseName]);
    $usersTableExists = (int) $tableExistsStmt->fetchColumn() > 0;

    if (!$usersTableExists) {
        $conn->exec("
            CREATE TABLE users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                avatar VARCHAR(255) DEFAULT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'manager', 'editor') DEFAULT 'editor',
                last_login DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status TINYINT(1) DEFAULT 1
            )
        ");
    } else {
        $columnsStmt = $conn->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = :schema_name
              AND TABLE_NAME = 'users'
        ");
        $columnsStmt->execute([':schema_name' => $databaseName]);
        $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        $existingColumns = array_flip($existingColumns);

        $columnDefinitions = [
            'first_name' => "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT ''",
            'last_name' => "ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT ''",
            'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT ''",
            'avatar' => "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL"
        ];

        foreach ($columnDefinitions as $columnName => $sql) {
            if (!isset($existingColumns[$columnName])) {
                $conn->exec($sql);
            }
        }

        $columnsStmt->execute([':schema_name' => $databaseName]);
        $existingColumns = array_flip($columnsStmt->fetchAll(PDO::FETCH_COLUMN));

        if (isset($existingColumns['full_name'])) {
            $conn->exec("
                UPDATE users
                SET
                    first_name = CASE
                        WHEN COALESCE(first_name, '') = '' THEN SUBSTRING_INDEX(full_name, ' ', 1)
                        ELSE first_name
                    END,
                    last_name = CASE
                        WHEN COALESCE(last_name, '') = '' THEN TRIM(SUBSTRING(full_name, LENGTH(SUBSTRING_INDEX(full_name, ' ', 1)) + 1))
                        ELSE last_name
                    END,
                    phone = COALESCE(phone, ''),
                    full_name = CASE
                        WHEN COALESCE(full_name, '') = '' THEN TRIM(CONCAT(first_name, ' ', last_name))
                        ELSE full_name
                    END
            ");
        } else {
            $conn->exec("
                UPDATE users
                SET
                    first_name = COALESCE(first_name, ''),
                    last_name = COALESCE(last_name, ''),
                    phone = COALESCE(phone, '')
            ");
        }
    }

    $adminUsers = [
        [
            'username' => 'mamad',
            'first_name' => 'Mamad',
            'last_name' => 'Admin',
            'phone' => '09120000000',
            'email' => 'mamad@poweradmin.local',
            'password' => '$2b$10$HECFylTzd8UGHIpoWhVLV.bNgwDNzK24F03Ka2K7rMzMWLtLEPgBu',
            'full_name' => 'Mamad Admin'
        ],
        [
            'username' => 'mamad2',
            'first_name' => 'Mamad',
            'last_name' => 'Admin 2',
            'phone' => '09120000001',
            'email' => 'mamad2@poweradmin.local',
            'password' => '$2b$10$9I9JGSYj2ClaMMIZO1Ev2OKQR4DdpRhLHkb0JkTqoqVDUAl1CAnGi',
            'full_name' => 'Mamad Admin 2'
        ]
    ];

    $upsertUser = $conn->prepare("
        INSERT INTO users (username, first_name, last_name, phone, email, password, avatar, full_name, role, status)
        VALUES (:username, :first_name, :last_name, :phone, :email, :password, NULL, :full_name, 'admin', 1)
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            role = 'admin',
            status = 1,
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            phone = VALUES(phone),
            full_name = VALUES(full_name)
    ");

    foreach ($adminUsers as $adminUser) {
        $upsertUser->execute([
            ':username' => $adminUser['username'],
            ':first_name' => $adminUser['first_name'],
            ':last_name' => $adminUser['last_name'],
            ':phone' => $adminUser['phone'],
            ':email' => $adminUser['email'],
            ':password' => $adminUser['password'],
            ':full_name' => $adminUser['full_name']
        ]);
    }

    echo "Users migration completed successfully.\n";
    echo "Database: " . $databaseName . "\n";
    echo "Server version: " . $version . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Users migration failed: " . $e->getMessage() . "\n";
}

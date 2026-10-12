<?php
/** Apply the checked-in SQL schema to the configured database (CLI only). */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}

require_once dirname(__DIR__).'/config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->set_charset('utf8mb4');
$schema = file_get_contents(dirname(__DIR__).'/internship.sql');

function has_column($db, $table, $column) {
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && $result->num_rows > 0;
}

function column_type($db, $table, $column) {
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if (!$result || !$result->num_rows) return '';
    $row = $result->fetch_assoc();
    return strtolower($row['Type']);
}

function has_index($db, $table, $index) {
    $table = $db->real_escape_string($table);
    $index = $db->real_escape_string($index);
    $result = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
    return $result && $result->num_rows > 0;
}

function has_foreign_key($db, $table, $name) {
    $statement = $db->prepare('SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\' LIMIT 1');
    $statement->bind_param('ss', $table, $name);
    $statement->execute();
    $result = $statement->get_result();
    $found = $result->num_rows > 0;
    $statement->close();
    return $found;
}

function add_column_if_missing($db, $table, $column, $definition) {
    if (!has_column($db, $table, $column)) {
        $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function add_foreign_key_if_safe($db, $table, $name, $column, $parent, $parentColumn, $onDelete) {
    if (has_foreign_key($db, $table, $name)) return true;
    $orphans = $db->query("SELECT COUNT(*) AS total FROM `{$table}` child LEFT JOIN `{$parent}` parent ON parent.`{$parentColumn}` = child.`{$column}` WHERE child.`{$column}` IS NOT NULL AND parent.`{$parentColumn}` IS NULL")->fetch_assoc();
    if ((int)$orphans['total'] > 0) return false;
    $db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$parent}` (`{$parentColumn}`) ON DELETE {$onDelete}");
    return true;
}

try {
    if ($schema === false || trim($schema) === '') {
        throw new RuntimeException('internship.sql is empty or could not be read.');
    }

    // Normalize legacy signed keys before CREATE TABLE adds relationships to them.
    // This keeps fresh installs fully constrained and lets older installs upgrade
    // without dropping rows. Negative identifiers cannot be represented as UNSIGNED.
    if ($db->query("SHOW TABLES LIKE 'internships'")->num_rows > 0 && has_column($db, 'internships', 'id') && strpos(column_type($db, 'internships', 'id'), 'unsigned') === false) {
        $negativeListingIds = $db->query('SELECT COUNT(*) AS total FROM internships WHERE id < 0')->fetch_assoc();
        if ((int)$negativeListingIds['total'] > 0) {
            throw new RuntimeException('Internships contain negative IDs; resolve them before converting IDs to unsigned.');
        }
        $db->query('ALTER TABLE internships MODIFY id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT');
    }
    if ($db->query("SHOW TABLES LIKE 'applications'")->num_rows > 0) {
        $legacyApplicationColumns = array(
            'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
            'cus_id' => 'INT(10) UNSIGNED NOT NULL',
            'int_id' => 'INT(10) UNSIGNED NOT NULL'
        );
        foreach ($legacyApplicationColumns as $column => $definition) {
            if (!has_column($db, 'applications', $column) || strpos(column_type($db, 'applications', $column), 'unsigned') !== false) continue;
            $negativeIds = $db->query("SELECT COUNT(*) AS total FROM applications WHERE `{$column}` < 0")->fetch_assoc();
            if ((int)$negativeIds['total'] > 0) {
                throw new RuntimeException('Applications contain negative IDs; resolve them before converting ID columns to unsigned.');
            }
            $db->query("ALTER TABLE applications MODIFY `{$column}` {$definition}");
        }
    }

    if (!$db->multi_query($schema)) {
        throw new RuntimeException($db->error);
    }
    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());

    // Extend databases created by earlier versions without dropping user data.
    add_column_if_missing($db, 'users', 'account_status', "ENUM('active','pending','suspended','deactivated') NOT NULL DEFAULT 'active'");
    add_column_if_missing($db, 'users', 'email_verified_at', 'DATETIME DEFAULT NULL');
    add_column_if_missing($db, 'users', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

    $internshipsHadStatus = has_column($db, 'internships', 'status');
    add_column_if_missing($db, 'internships', 'employer_id', 'INT(10) UNSIGNED DEFAULT NULL');
    add_column_if_missing($db, 'internships', 'category_id', 'INT(10) UNSIGNED DEFAULT NULL');
    add_column_if_missing($db, 'internships', 'status', "ENUM('draft','pending','published','closed','rejected') NOT NULL DEFAULT 'pending'");
    add_column_if_missing($db, 'internships', 'closing_at', 'DATETIME DEFAULT NULL');
    add_column_if_missing($db, 'internships', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    add_column_if_missing($db, 'internships', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    if (!$internshipsHadStatus) {
        // Listings were public before moderation existed; preserve that visibility.
        $db->query("UPDATE internships SET status = 'published'");
    }
    if (strpos(column_type($db, 'internships', 'id'), 'unsigned') === false) {
        $negativeListingIds = $db->query('SELECT COUNT(*) AS total FROM internships WHERE id < 0')->fetch_assoc();
        if ((int)$negativeListingIds['total'] > 0) {
            throw new RuntimeException('Internships contain negative IDs; resolve them before converting IDs to unsigned.');
        }
        $db->query('ALTER TABLE internships MODIFY id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT');
    }
    if (!has_index($db, 'internships', 'internships_public_feed_index')) {
        $db->query('ALTER TABLE internships ADD INDEX internships_public_feed_index (status, posted_at)');
    }
    if (!has_index($db, 'internships', 'internships_employer_index')) {
        $db->query('ALTER TABLE internships ADD INDEX internships_employer_index (employer_id, status)');
    }
    if (!has_index($db, 'internships', 'internships_category_index')) {
        $db->query('ALTER TABLE internships ADD INDEX internships_category_index (category_id, status)');
    }
    add_foreign_key_if_safe($db, 'internships', 'internships_employer_fk', 'employer_id', 'users', 'id', 'SET NULL');
    add_foreign_key_if_safe($db, 'internships', 'internships_category_fk', 'category_id', 'internship_categories', 'id', 'SET NULL');

    add_column_if_missing($db, 'applications', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    $db->query("UPDATE applications SET status = 'pending' WHERE status IS NULL");
    $db->query("ALTER TABLE applications MODIFY status ENUM('pending','reviewed','shortlisted','rejected','hired') NOT NULL DEFAULT 'pending'");
    $modifyApplicationColumns = array();
    foreach (array('id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT', 'cus_id' => 'INT(10) UNSIGNED NOT NULL', 'int_id' => 'INT(10) UNSIGNED NOT NULL') as $column => $definition) {
        if (strpos(column_type($db, 'applications', $column), 'unsigned') === false) {
            $negativeIds = $db->query("SELECT COUNT(*) AS total FROM applications WHERE `{$column}` < 0")->fetch_assoc();
            if ((int)$negativeIds['total'] > 0) {
                throw new RuntimeException('Applications contain negative IDs; resolve them before converting ID columns to unsigned.');
            }
            $modifyApplicationColumns[] = "MODIFY `{$column}` {$definition}";
        }
    }
    if ($modifyApplicationColumns) {
        $db->query('ALTER TABLE applications '.implode(', ', $modifyApplicationColumns));
    }
    if (!has_index($db, 'applications', 'applications_student_internship_unique')) {
        $duplicates = $db->query('SELECT COUNT(*) AS total FROM (SELECT cus_id, int_id FROM applications GROUP BY cus_id, int_id HAVING COUNT(*) > 1) d')->fetch_assoc();
        if ((int)$duplicates['total'] === 0) {
            $db->query('ALTER TABLE applications ADD UNIQUE INDEX applications_student_internship_unique (cus_id, int_id)');
        }
    }
    $userForeignKeyAdded = add_foreign_key_if_safe($db, 'applications', 'applications_student_fk', 'cus_id', 'users', 'id', 'RESTRICT');
    $listingForeignKeyAdded = add_foreign_key_if_safe($db, 'applications', 'applications_internship_fk', 'int_id', 'internships', 'id', 'RESTRICT');
    $historyForeignKeyAdded = add_foreign_key_if_safe($db, 'application_status_history', 'application_history_application_fk', 'application_id', 'applications', 'id', 'CASCADE');

    fwrite(STDOUT, "Core schema applied and existing tables upgraded without dropping rows.\n");
    if (!$userForeignKeyAdded) fwrite(STDOUT, "Warning: applications refer to missing user records; the user foreign key was skipped.\n");
    if (!$listingForeignKeyAdded) fwrite(STDOUT, "Warning: applications refer to missing internship records; the internship foreign key was skipped to preserve application history.\n");
    if (!$historyForeignKeyAdded) fwrite(STDOUT, "Warning: status-history rows refer to missing applications; the history foreign key was skipped.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Schema setup failed: ".$exception->getMessage()."\n");
    exit(1);
} finally {
    $db->close();
}

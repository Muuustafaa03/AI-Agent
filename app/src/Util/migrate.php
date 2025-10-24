<?php
// src/Util/migrate.php
declare(strict_types=1);

/**
 * Idempotent DB bootstrap. Creates tables/columns if missing.
 * Safe to call on every request; it only applies deltas when needed.
 */

function migrate_once(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = \db();
    $db->set_charset('utf8mb4');

    // Ensure database is selected (Railway default DB is 'railway')
    $db->query("CREATE TABLE IF NOT EXISTS runs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        status ENUM('pending','running','succeeded','failed') NOT NULL DEFAULT 'pending',
        input_type VARCHAR(32) NOT NULL,
        input_payload LONGTEXT NOT NULL,
        title VARCHAR(255) NULL,
        predicted_priority VARCHAR(8) NULL,
        final_priority VARCHAR(8) NULL,
        model_version VARCHAR(64) NULL,
        prompt_tokens INT NULL,
        completion_tokens INT NULL,
        total_tokens INT NULL,
        latency_ms INT NULL,
        est_cost_usd DECIMAL(10,6) NULL,
        started_at DATETIME NULL,
        finished_at DATETIME NULL,
        error_msg TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_runs_created (created_at),
        KEY idx_runs_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS artifacts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        run_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(64) NOT NULL,
        storage_path TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_artifacts_run (run_id),
        CONSTRAINT fk_artifacts_runs
          FOREIGN KEY (run_id) REFERENCES runs(id)
          ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS run_steps (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        run_id BIGINT UNSIGNED NOT NULL,
        step_name VARCHAR(64) NOT NULL,
        status ENUM('pending','running','succeeded','failed') NOT NULL DEFAULT 'pending',
        started_at DATETIME NULL,
        finished_at DATETIME NULL,
        duration_ms INT NULL,
        info_json JSON NULL,
        PRIMARY KEY (id),
        KEY idx_steps_run (run_id),
        KEY idx_steps_started (started_at),
        CONSTRAINT fk_steps_runs
          FOREIGN KEY (run_id) REFERENCES runs(id)
          ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Helper function to check if column exists
    $columnExists = function($table, $column) use ($db): bool {
        $dbName = env('DB_DATABASE', 'railway');
        $result = $db->query("
            SELECT COUNT(*) as count 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = '$dbName' 
            AND TABLE_NAME = '$table' 
            AND COLUMN_NAME = '$column'
        ");
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    };

    // Columns that might be missing if DB existed before
    $maybeCols = [
        'runs' => [
            ['title', "ALTER TABLE runs ADD COLUMN title VARCHAR(255) NULL"],
            ['model_version', "ALTER TABLE runs ADD COLUMN model_version VARCHAR(64) NULL"],
            ['prompt_tokens', "ALTER TABLE runs ADD COLUMN prompt_tokens INT NULL"],
            ['completion_tokens', "ALTER TABLE runs ADD COLUMN completion_tokens INT NULL"],
            ['total_tokens', "ALTER TABLE runs ADD COLUMN total_tokens INT NULL"],
            ['latency_ms', "ALTER TABLE runs ADD COLUMN latency_ms INT NULL"],
            ['est_cost_usd', "ALTER TABLE runs ADD COLUMN est_cost_usd DECIMAL(10,6) NULL"],
            ['started_at', "ALTER TABLE runs ADD COLUMN started_at DATETIME NULL"],
            ['finished_at', "ALTER TABLE runs ADD COLUMN finished_at DATETIME NULL"],
            ['error_msg', "ALTER TABLE runs ADD COLUMN error_msg TEXT NULL"]
        ],
    ];

    foreach ($maybeCols as $table => $columns) {
        foreach ($columns as [$columnName, $sql]) {
            if (!$columnExists($table, $columnName)) {
                $db->query($sql);
            }
        }
    }
}
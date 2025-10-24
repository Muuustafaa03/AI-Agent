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

    // Columns that might be missing if DB existed before
    $maybeCols = [
        'runs' => [
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS model_version VARCHAR(64) NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS prompt_tokens INT NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS completion_tokens INT NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS total_tokens INT NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS latency_ms INT NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS est_cost_usd DECIMAL(10,6) NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS started_at DATETIME NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS finished_at DATETIME NULL",
            "ALTER TABLE runs ADD COLUMN IF NOT EXISTS error_msg TEXT NULL"
        ],
    ];

    foreach ($maybeCols as $table => $stmts) {
        foreach ($stmts as $sql) {
            // MySQL 8 doesn't support IF NOT EXISTS for all ALTERs; ignore failures
            @$db->query($sql);
        }
    }
}

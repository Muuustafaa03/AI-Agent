CREATE DATABASE IF NOT EXISTS agentdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'agentuser'@'%' IDENTIFIED BY 'agentpass';
GRANT ALL PRIVILEGES ON agentdb.* TO 'agentuser'@'%';
FLUSH PRIVILEGES;

USE agentdb;

CREATE TABLE IF NOT EXISTS runs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  status ENUM('pending','running','succeeded','failed') NOT NULL DEFAULT 'pending',
  input_type ENUM('url','text') NOT NULL,
  input_payload TEXT NOT NULL,
  predicted_priority ENUM('P1','P2','P3') NULL,
  final_priority ENUM('P1','P2','P3') NULL,
  model_version VARCHAR(50) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS run_steps (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT NOT NULL,
  step_index INT NOT NULL,
  tool_name VARCHAR(64) NOT NULL,
  input_json JSON,
  output_json JSON,
  status ENUM('queued','ok','error') NOT NULL DEFAULT 'queued',
  started_at TIMESTAMP NULL,
  finished_at TIMESTAMP NULL,
  FOREIGN KEY (run_id) REFERENCES runs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS artifacts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT NOT NULL,
  type VARCHAR(64) NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  bytes INT NULL,
  meta_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (run_id) REFERENCES runs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feedback (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT NOT NULL,
  rating ENUM('up','down') NOT NULL,
  notes TEXT NULL,
  new_priority ENUM('P1','P2','P3') NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (run_id) REFERENCES runs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS models (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  version VARCHAR(50) NOT NULL UNIQUE,
  path VARCHAR(255) NOT NULL,
  metrics_json JSON,
  trained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
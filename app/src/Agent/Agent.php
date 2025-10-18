<?php
namespace App\Agent;

class Agent {
  public function createRun(string $inputType, string $payload): int {
    $db = \db();
    $stmt = $db->prepare("INSERT INTO runs (status, input_type, input_payload) VALUES ('pending', ?, ?)");
    $stmt->bind_param('ss', $inputType, $payload);
    $stmt->execute();
    return $stmt->insert_id;
  }

  public function execute(int $runId, string $template): bool {
    $db = \db();
    $db->query("UPDATE runs SET status='running' WHERE id=$runId");

    // Predict priority (Python worker stub)
    $priority = $this->predictPriority($runId);

    // Fetch content if URL
    $res = $db->query("SELECT input_type, input_payload FROM runs WHERE id=$runId");
    $row = $res->fetch_assoc();
    $text = $row['input_payload'];
    if ($row['input_type'] === 'url') {
      $text = \App\Skills\Tools::fetch_url($row['input_payload']);
    }

    // Plan (simple MVP)
    $outputs = [];
    if ($template === 'task_breakdown') {
      $outputs['tasks'] = \App\Skills\Tools::task_breakdown($text);
    } else {
      $style = $template === 'technical_summary' ? 'technical' : 'brief';
      $outputs['summary'] = \App\Skills\Tools::summarize($text, $style);
    }

    // Save an artifact
    $path = "/var/www/storage/artifacts/run_${runId}.json";
    file_put_contents($path, json_encode($outputs, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    $stmt = $db->prepare("INSERT INTO artifacts (run_id, type, storage_path) VALUES (?, 'json', ?)");
    $stmt->bind_param('is', $runId, $path);
    $stmt->execute();

    $stmt = $db->prepare("UPDATE runs SET status='succeeded', predicted_priority=?, final_priority=? WHERE id=?");
    $stmt->bind_param('ssi', $priority, $priority, $runId);
    $stmt->execute();

    return true;
  }

  private function predictPriority(int $runId): string {
    $url = env('PY_WORKER_URL', 'http://python:5001') . '/predict';
    $payload = ['run_id' => $runId];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) return 'P3';
    $data = json_decode($resp, true);
    return $data['priority'] ?? 'P3';
  }
}
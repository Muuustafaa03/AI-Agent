<?php
namespace App\Agent;

use App\Skills\Tools;

class Agent {

  private function logStep(\mysqli $db, int $runId, string $name, string $status, array $info = []): void {
    $info_json = json_encode($info, JSON_UNESCAPED_UNICODE);
    $stmt = $db->prepare(
      "INSERT INTO run_steps (run_id, step_name, status, started_at, finished_at, duration_ms, info_json)
       VALUES (?, ?, ?, NOW(), NOW(), ?, ?)"
    );
    $dur = $info['duration_ms'] ?? null;
    $stmt->bind_param('issis', $runId, $name, $status, $dur, $info_json);
    $stmt->execute();
  }

  public function createRun(string $inputType, string $payload): int {
    $db = \db();
    $title = $this->generateTitle($inputType, $payload, $_POST['template'] ?? 'research_brief');
    $stmt = $db->prepare("INSERT INTO runs (status, input_type, input_payload, title, created_at) VALUES ('pending', ?, ?, ?, NOW())");
    $stmt->bind_param('sss', $inputType, $payload, $title);
    $stmt->execute();
    return $stmt->insert_id;
  }

  public function execute(int $runId, string $template): bool {
    $db = \db();
    $db->query("UPDATE runs SET status='running', started_at=NOW(), error_msg=NULL WHERE id={$runId}");

    $t0 = microtime(true);
    $priority = $this->predictPriority($runId);

    try {
      // 1) Fetch
      $this->logStep($db, $runId, 'fetch', 'running');
      $row = $db->query("SELECT input_type, input_payload FROM runs WHERE id={$runId}")->fetch_assoc();
      $text = $row['input_payload'];
      if ($row['input_type'] === 'url') {
        $text = \App\Skills\Tools::fetch_url($row['input_payload']);
      }
      $this->logStep($db, $runId, 'fetch', 'succeeded', ['bytes'=>strlen($text)]);

      // 2) Summarize (template-aware)
      $this->logStep($db, $runId, 'summarize', 'running');
      $style = ($template === 'technical_summary') ? 'technical' : 'brief';

      $sumResp = $this->postJson(\env('PY_WORKER_URL','http://python:5001').'/summarize', [
        'run_id'   => $runId,
        'text'     => mb_substr($text, 0, 100_000),
        'template' => $template,
        'style'    => $style
      ]);

      $model = 'unknown';
      $usage = ['prompt_tokens'=>0,'completion_tokens'=>0];
      $est_cost = null;
      $latency_ms_worker = null;
      $outputs = [];

      if ($sumResp && empty($sumResp['error'])) {
        $model = $sumResp['model'] ?? $model;
        if (isset($sumResp['usage'])) {
          $usage['prompt_tokens'] = (int)($sumResp['usage']['prompt_tokens'] ?? 0);
          $usage['completion_tokens'] = (int)($sumResp['usage']['completion_tokens'] ?? 0);
        }
        $est_cost = isset($sumResp['est_cost_usd']) ? (float)$sumResp['est_cost_usd'] : null;
        $latency_ms_worker = isset($sumResp['latency_ms']) ? (int)$sumResp['latency_ms'] : null;

        switch ($sumResp['type'] ?? 'summary') {
          case 'tasks':
            // The worker returns a Markdown string; store as-is
            $outputs = ['tasks_md' => (string)($sumResp['tasks_md'] ?? '')];
            break;
          case 'classification':
            $outputs = ['classification_md' => (string)($sumResp['classification_md'] ?? '')];
            break;
          case 'prd':
            $outputs = ['prd_md' => (string)($sumResp['prd_md'] ?? '')];
            break;
          default:
            $outputs = ['summary' => (string)($sumResp['summary'] ?? '')];
        }
        $this->logStep($db, $runId, 'summarize', 'succeeded', ['model'=>$model, 'usage'=>$usage, 'latency_ms'=>$latency_ms_worker]);
      } else {
        // fallback to local heuristic
        if ($template === 'task_breakdown') {
          $outputs = ['tasks_md' => "- " . implode("\n- ", Tools::task_breakdown($text))];
        } else {
          $outputs = ['summary' => Tools::summarize($text, $style)];
        }
        $pred = $this->postJson(\env('PY_WORKER_URL','http://python:5001').'/predict', ['run_id'=>$runId,'text'=>mb_substr($text,0,8000)]);
        if ($pred && isset($pred['usage'])) $usage = $pred['usage'];
        $model = $pred['model'] ?? 'gpt-4o-mini';
        $est_cost = isset($pred['est_cost_usd']) ? (float)$pred['est_cost_usd'] : null;
        $latency_ms_worker = isset($pred['latency_ms']) ? (int)$pred['latency_ms'] : null;
        $this->logStep($db, $runId, 'summarize', 'succeeded', ['fallback'=>true, 'model'=>$model, 'usage'=>$usage]);
      }

      // 3) Save artifact
      $this->logStep($db, $runId, 'save_artifact', 'running');
      $path = "/var/www/storage/artifacts/run_{$runId}.json";
      file_put_contents($path, json_encode($outputs, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
      $stmt = $db->prepare("INSERT INTO artifacts (run_id, type, storage_path) VALUES (?, 'json', ?) ON DUPLICATE KEY UPDATE storage_path=VALUES(storage_path)");
      $stmt->bind_param('is', $runId, $path);
      @$stmt->execute();
      $this->logStep($db, $runId, 'save_artifact', 'succeeded', ['path'=>$path]);

      // 4) KPIs
      $latency_total = (int) round((microtime(true) - $t0) * 1000);
      $prompt = (int)($usage['prompt_tokens'] ?? 0);
      $compl  = (int)($usage['completion_tokens'] ?? 0);
      $total  = $prompt + $compl;

      $stmt = $db->prepare("
        UPDATE runs
           SET status='succeeded',
               predicted_priority=?,
               final_priority=?,
               model_version=?,
               prompt_tokens=?,
               completion_tokens=?,
               total_tokens=?,
               latency_ms=?,
               est_cost_usd=?,
               finished_at=NOW()
         WHERE id=?
      ");
      $stmt->bind_param(
        'sssiiiidi',
        $priority, $priority, $model,
        $prompt, $compl, $total,
        $latency_total, $est_cost,
        $runId
      );
      $stmt->execute();

      return true;

    } catch (\Throwable $e) {
      $latency = (int) round((microtime(true) - $t0) * 1000);
      $msg = $e->getMessage();
      $this->logStep($db, $runId, 'error', 'failed', ['error'=>$msg]);

      $stmt = $db->prepare("
        UPDATE runs
           SET status='failed',
               final_priority=?,
               error_msg=?,
               latency_ms=?,
               finished_at=NOW()
         WHERE id=?
      ");
      $p = $priority ?? 'P3';
      $stmt->bind_param('ssii', $p, $msg, $latency, $runId);
      $stmt->execute();
      return false;
    }
  }

  private function postJson(string $url, array $payload): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
      CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    if ($resp === false || $resp === null) return null;
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
  }

  private function predictPriority(int $runId): string {
    $url = \env('PY_WORKER_URL', 'http://python:5001') . '/predict';
    $payload = ['run_id' => $runId, 'text' => 'priority seed'];
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

  private function generateTitle(string $inputType, string $payload, string $template): string {
    $templateMap = [
      'research_brief'    => 'Research Brief',
      'technical_summary' => 'Technical Summary',
      'task_breakdown'    => 'Task Breakdown',
      'classify_topic'    => 'Topic Classification',
      'prd_outline'       => 'PRD Outline',
    ];
    $kind = $templateMap[$template] ?? ucfirst(str_replace('_',' ',$template));

    if ($inputType === 'url') {
      $host = parse_url($payload, PHP_URL_HOST) ?? 'source';
      $pageTitle = \App\Skills\Tools::fetch_title($payload) ?: $host;
      $source = str_contains($host, 'wikipedia.org')
        ? ' (Wikipedia)'
        : ' (' . $host . ')';
      if ($template === 'task_breakdown') {
        return "Task Breakdown — {$pageTitle}{$source}";
      }
      return "Summary of {$pageTitle} — {$kind}{$source}";
    } else {
      $snippet = trim(preg_replace('/\s+/', ' ', mb_substr($payload, 0, 60)));
      if ($template === 'task_breakdown') {
        return "Task Breakdown — “{$snippet}…”";
      }
      return "Summary of “{$snippet}…” — {$kind}";
    }
  }
}

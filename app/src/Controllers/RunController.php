<?php
namespace App\Controllers;
use App\Agent\Agent;

class RunController {
  public function create() {
    $inputType = $_POST['input_type'] ?? 'url';
    $payload   = $_POST['input_payload'] ?? '';
    $template  = $_POST['template'] ?? 'research_brief';

    if ($payload === '') { header('Location: /?err=Please+enter+a+URL+or+text'); return; }
    if ($inputType === 'url' && !filter_var($payload, FILTER_VALIDATE_URL)) {
      header('Location: /?err=Invalid+URL'); return;
    }

    $agent = new Agent();
    $id = $agent->createRun($inputType, $payload);
    $agent->execute($id, $template);
    header('Location: /runs?id='.$id);
  }

  public function list() {
    $db = \db();
    $res = $db->query("SELECT id, status, input_type, title, LEFT(input_payload, 100) as preview, created_at FROM runs ORDER BY id DESC LIMIT 100");

    echo '<!doctype html><meta charset="utf-8">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">';
    include __DIR__ . '/../../public/theme.php';

    echo '<div class="max-w-4xl mx-auto py-10">';
    echo '<div class="flex justify-between items-center mb-4">';
    echo '  <a class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800" href="/">← Back</a>';
    echo '  <div class="flex gap-2">';
    echo '    <a class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800" href="/analytics">Analytics</a>';
    echo '    <button data-theme-toggle onclick="toggleTheme()" class="text-sm px-3 py-1 rounded border">Dark Mode</button>';
    echo '  </div>';
    echo '</div>';

    echo '<h1 class="text-2xl font-bold mb-4">Runs</h1>';
    echo '<div class="mt-4 space-y-2">';
    while ($row = $res->fetch_assoc()) {
      $id = (int)$row['id'];
      $title = $row['title'] ?: ('Run #'.$id);
      echo '<a href="/runs?id='.$id.'" class="block bg-white dark:bg-gray-900 p-4 rounded border shadow hover:bg-gray-50 dark:hover:bg-gray-800">';
      echo   '<div class="font-semibold">'.htmlspecialchars($title).' — '.ucfirst($row['status']).'</div>';
      echo   '<div class="text-sm text-gray-600 dark:text-gray-300">'.htmlspecialchars($row['input_type']).' — '.htmlspecialchars($row['preview']).'</div>';
      echo '</a>';
    }
    echo '</div></div>';
  }

  public function delete() {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { header('Location: /runs'); return; }
    $db = \db();
    $res = $db->query("SELECT storage_path FROM artifacts WHERE run_id={$id}");
    while ($r = $res->fetch_assoc()) {
      $p = $r['storage_path'];
      if ($p && is_file($p)) @unlink($p);
    }
    $db->query("DELETE FROM runs WHERE id={$id}");
    header('Location: /runs');
  }

  public function save() {
    $id = (int)($_GET['id'] ?? 0);
    $text = $_POST['text'] ?? '';
    if ($id <= 0) { http_response_code(400); echo 'Missing id'; return; }

    $db = \db();
    $stmt = $db->prepare("UPDATE runs SET edited_summary=? WHERE id=?");
    $stmt->bind_param('si', $text, $id);
    $stmt->execute();

    $safe = preg_replace('/[^a-zA-Z0-9_\-]+/','_', (string)$id);
    $path = "/var/www/storage/artifacts/run_{$safe}_edited.txt";
    file_put_contents($path, $text);
    @$db->query("INSERT INTO artifacts (run_id, type, storage_path) VALUES ({$id}, 'edited_txt', '{$db->real_escape_string($path)}')");

    header('Content-Type: application/json');
    echo json_encode(['ok'=>true]);
  }

  public function show(int $id) {
    $db = \db();
    $run = $db->query("SELECT * FROM runs WHERE id={$id}")->fetch_assoc();
    if (!$run) { http_response_code(404); echo 'Run not found'; return; }

    $art = $db->query("SELECT * FROM artifacts WHERE run_id={$id} ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $json = ($art && is_file($art['storage_path'])) ? file_get_contents($art['storage_path']) : '';

    echo '<!doctype html><meta charset="utf-8">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">';
    include __DIR__ . '/../../public/theme.php';

    echo '<div class="max-w-4xl mx-auto py-10">';
    echo '<div class="flex justify-between items-center mb-4">';
    echo '  <a class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800" href="/runs">← Back to runs</a>';
    echo '  <button data-theme-toggle onclick="toggleTheme()" class="text-sm px-3 py-1 rounded border">Dark Mode</button>';
    echo '</div>';

    $title = $run['title'] ?: ('Run #'.$run['id']);
    $statusBadge = ucfirst($run['status']);
    $statusColor = $run['status']==='failed' ? 'bg-red-600' : ($run['status']==='running' ? 'bg-yellow-500':'bg-green-600');

    echo '<h1 class="text-xl font-semibold">'.htmlspecialchars($title).'</h1>';
    echo '<div class="mt-2 mb-4 inline-block text-white text-xs px-2 py-1 rounded '.$statusColor.'">Run '.$statusBadge.'</div>';

    echo '<div class="grid md:grid-cols-4 gap-3 mb-4">';
    echo '  <div class="bg-white dark:bg-gray-900 p-3 rounded border"><div class="text-xs text-gray-600 dark:text-gray-300">Model</div><div class="font-semibold">'.htmlspecialchars($run['model_version'] ?: '—').'</div></div>';
    echo '  <div class="bg-white dark:bg-gray-900 p-3 rounded border"><div class="text-xs text-gray-600 dark:text-gray-300">Tokens</div><div class="font-semibold">'.(int)$run['total_tokens'].'</div></div>';
    echo '  <div class="bg-white dark:bg-gray-900 p-3 rounded border"><div class="text-xs text-gray-600 dark:text-gray-300">Latency</div><div class="font-semibold">'.(is_null($run['latency_ms'])?'—':((int)$run['latency_ms'].' ms')).'</div></div>';
    echo '  <div class="bg-white dark:bg-gray-900 p-3 rounded border"><div class="text-xs text-gray-600 dark:text-gray-300">Cost</div><div class="font-semibold">$'.(is_null($run['est_cost_usd'])?'0.000000':number_format((float)$run['est_cost_usd'],6)).'</div></div>';
    echo '</div>';

    echo '<div class="grid md:grid-cols-2 gap-3 mb-6">';
    echo '  <div class="bg-white dark:bg-gray-900 p-3 rounded border"><div class="text-xs text-gray-600 dark:text-gray-300">Started</div><div class="font-semibold">'.htmlspecialchars($run['started_at'] ?? '—').'</div></div>';
    echo '  <div class="bg-white dark:bg-gray-900 p-3 rounded border"><div class="text-xs text-gray-600 dark:text-gray-300">Finished</div><div class="font-semibold">'.htmlspecialchars($run['finished_at'] ?? '—').'</div></div>';
    echo '</div>';

    echo '<div class="bg-white dark:bg-gray-900 p-4 rounded border mb-6">';
    echo '<div class="text-sm text-gray-600 dark:text-gray-300 mb-2">Input ('.htmlspecialchars($run['input_type']).'):</div>';
    echo '<pre class="text-sm whitespace-pre-wrap">'.htmlspecialchars($run['input_payload']).'</pre>';
    echo '</div>';

    echo '<div class="bg-white dark:bg-gray-900 p-4 rounded border mb-6">';
    echo '<div class="flex items-center justify-between mb-2"><h2 class="font-semibold">Trace</h2><a class="text-sm underline" href="/runs?id='.$id.'">Refresh</a></div>';
    $steps = $db->query("SELECT step_name,status,started_at,finished_at,duration_ms,info_json FROM run_steps WHERE run_id={$id} ORDER BY id ASC");
    echo '<pre class="text-xs whitespace-pre-wrap">';
    while ($s = $steps->fetch_assoc()) {
      $line = sprintf("%-12s  %-9s  %s", $s['step_name'], $s['status'], $s['started_at']);
      echo htmlspecialchars($line)."\n";
      if (!empty($s['info_json'])) echo htmlspecialchars("  ↳ ". $s['info_json'])."\n";
    }
    if ($steps->num_rows === 0) echo "—\n";
    if (!empty($run['error_msg'])) echo htmlspecialchars("\nERROR: ".$run['error_msg'])."\n";
    echo '</pre>';
    echo '</div>';

    // ------- Artifact area -------
    $data = $json ? json_decode($json, true) : null;
    $content = '';
    if ($data) {
      if (isset($data['summary']))                 $content = $data['summary'];
      elseif (isset($data['tasks_md']))            $content = $data['tasks_md'];
      elseif (isset($data['classification_md']))   $content = $data['classification_md'];
      elseif (isset($data['prd_md']))              $content = $data['prd_md'];
      else                                         $content = $json;
    }

    echo '<div class="bg-white dark:bg-gray-900 p-6 rounded border">';
    echo '<div class="flex items-center justify-between mb-3">';
    echo '  <h2 class="text-lg font-semibold">Artifact</h2>';
    echo '  <div class="flex gap-2">';
    echo '    <button id="previewBtn" class="text-xs px-2 py-1 rounded border">Preview</button>';
    echo '    <button id="copyBtn" class="text-xs px-2 py-1 rounded border">Copy</button>';
    echo '    <a id="dl" download="run_'.$id.'.txt" class="text-xs px-2 py-1 rounded border" href="#" onclick="downloadTxt()">Download .txt</a>';
    echo '    <a class="text-xs px-2 py-1 rounded border text-red-600" href="/runs/delete?id='.$id.'" onclick="return confirm(\'Delete this run?\')">Delete</a>';
    echo '    <button id="saveBtn" class="text-xs px-2 py-1 rounded border bg-blue-600 text-white">Save</button>';
    echo '  </div></div>';

    echo '<textarea id="artifactText" class="w-full text-sm font-mono border border-gray-300 dark:border-gray-700 rounded-lg p-3 leading-relaxed bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100" rows="16" spellcheck="true">'
        .htmlspecialchars($content).'</textarea>';
    echo '</div>';

    // We keep Marked loaded in THIS page, render here, and only send the HTML to the popup.
    echo '<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>';
    echo '<script>
const ta = document.getElementById("artifactText");
const copyBtn = document.getElementById("copyBtn");
const saveBtn = document.getElementById("saveBtn");
const previewBtn = document.getElementById("previewBtn");

previewBtn.addEventListener("click", () => {
  const html = (window.marked ? marked.parse(ta.value) : ta.value);
  const w = window.open("", "_blank");
  w.document.write("<!doctype html><meta charset=\\"utf-8\\"><title>Preview</title><style>body{font-family:system-ui,Segoe UI,Arial;max-width:800px;margin:40px auto;padding:0 16px;line-height:1.55;} pre,code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;}</style>");
  w.document.write(html);
  w.document.close();
});

copyBtn.addEventListener("click", () => {
  ta.select(); document.execCommand("copy");
  copyBtn.textContent = "Copied!"; setTimeout(()=>copyBtn.textContent="Copy",1200);
});

saveBtn.addEventListener("click", async () => {
  const body = new URLSearchParams(); body.append("text", ta.value);
  const res = await fetch("/runs/save?id='.$id.'", {method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body});
  if (res.ok) { saveBtn.textContent="Saved"; setTimeout(()=>saveBtn.textContent="Save",1000); }
});

function downloadTxt(){
  const blob = new Blob([ta.value], {type:"text/plain"});
  const url = URL.createObjectURL(blob);
  const a = document.getElementById("dl");
  a.href = url; setTimeout(()=>URL.revokeObjectURL(url), 1000);
}
</script>';

    echo '</div>';
  }
}

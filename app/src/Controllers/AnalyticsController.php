<?php
namespace App\Controllers;

class AnalyticsController {
  public function page() {
    $db = \db();

    // Totals
    $tot = $db->query("
      SELECT
        COUNT(*) AS runs,
        SUM(CASE WHEN status='succeeded' THEN 1 ELSE 0 END) AS ok,
        SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS fail,
        COALESCE(SUM(est_cost_usd),0) AS cost,
        COALESCE(AVG(latency_ms),0) AS avg_lat
      FROM runs
    ")->fetch_assoc();

    // Cost over time (last 14 days)
    $costRows = $db->query("
      SELECT DATE(created_at) d, COALESCE(SUM(est_cost_usd),0) c
      FROM runs
      WHERE created_at >= NOW() - INTERVAL 14 DAY
      GROUP BY DATE(created_at)
      ORDER BY d ASC
    ");

    // Model share
    $modelRows = $db->query("
      SELECT COALESCE(model_version,'unknown') m, COUNT(*) n
      FROM runs
      GROUP BY m
      ORDER BY n DESC
    ");

    // Success/fail per day (last 14 days)
    $sfRows = $db->query("
      SELECT DATE(created_at) d,
             SUM(status='succeeded') s,
             SUM(status='failed') f
      FROM runs
      WHERE created_at >= NOW() - INTERVAL 14 DAY
      GROUP BY DATE(created_at)
      ORDER BY d ASC
    ");

    // Top 10 expensive runs
    $exp = $db->query("
      SELECT id, title, COALESCE(est_cost_usd,0) c, created_at
      FROM runs
      ORDER BY c DESC
      LIMIT 10
    ");

    echo '<!doctype html><meta charset="utf-8">';
    echo '<title>Analytics — AI Agent</title>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">';
    include __DIR__ . '/../../public/theme.php';

    echo '<div class="max-w-5xl mx-auto py-10">';
    echo '<div class="flex justify-between items-center mb-6">';
    echo '  <a href="/" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">← Back</a>';
    echo '  <div class="flex gap-2">';
    echo '    <a href="/runs" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">View Runs</a>';
    echo '    <a href="/errors" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">Errors</a>';
    echo '    <button data-theme-toggle onclick="toggleTheme()" class="text-sm px-3 py-1 rounded border">Dark Mode</button>';
    echo '  </div>';
    echo '</div>';

    // Stat cards
    echo '<div class="grid md:grid-cols-4 gap-3 mb-6">';
    echo card('Total Runs', (int)$tot['runs']);
    echo card('Succeeded', (int)$tot['ok']);
    echo card('Failed', (int)$tot['fail']);
    echo card('Total Cost', '$'.number_format((float)$tot['cost'], 6));
    echo '</div>';
    echo '<div class="grid md:grid-cols-3 gap-3 mb-10">';
    echo card('Avg Latency', ((int)$tot['avg_lat']).' ms');
    echo '<div class="rounded border bg-white dark:bg-gray-900 p-3"><div class="text-xs text-gray-600 dark:text-gray-300 mb-2">Export</div>'
        .'<div class="flex gap-2 flex-wrap">'
        .'<a class="text-xs px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800" href="/analytics/export?format=csv">CSV (runs)</a>'
        .'<a class="text-xs px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800" href="/analytics/export?format=json">JSON (runs)</a>'
        .'</div></div>';
    echo '<div class="rounded border bg-white dark:bg-gray-900 p-3"><div class="text-xs text-gray-600 dark:text-gray-300 mb-2">Tip</div>'
        .'<div class="text-sm">Use <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800">/analytics/export</code> to pipe data into notebooks.</div></div>';
    echo '</div>';

    // Charts
    echo '<div class="grid md:grid-cols-2 gap-6">';

    // Cost line
    $labels = []; $values = [];
    while ($r = $costRows->fetch_assoc()) { $labels[]=$r['d']; $values[]=(float)$r['c']; }
    echo chartBox('Cost (last 14 days)', 'costChart', $labels, $values, 'line');

    // Model pie
    $mlabels = []; $mvals = [];
    while ($r = $modelRows->fetch_assoc()) { $mlabels[]=$r['m'] ?: 'unknown'; $mvals[]=(int)$r['n']; }
    echo chartBox('Runs by Model', 'modelChart', $mlabels, $mvals, 'pie');

    echo '</div>';

    // Success/Fail stacked
    $slabels=[]; $svals=[]; $fvals=[];
    while ($r = $sfRows->fetch_assoc()) { $slabels[]=$r['d']; $svals[]=(int)$r['s']; $fvals[]=(int)$r['f']; }
    echo '<div class="mt-6">'.stackedBox('Success vs Fail (14d)', 'sfChart', $slabels, $svals, $fvals).'</div>';

    // Expensive runs table
    echo '<div class="mt-8 bg-white dark:bg-gray-900 rounded border p-4">';
    echo '<div class="font-semibold mb-3">Top 10 Most Expensive Runs</div>';
    echo '<div class="overflow-x-auto"><table class="w-full text-sm">';
    echo '<thead><tr class="text-left text-gray-600 dark:text-gray-300">'
        .'<th class="py-2 pr-4">Run</th><th class="py-2 pr-4">Cost</th><th class="py-2 pr-4">Created</th></tr></thead><tbody>';
    while ($r = $exp->fetch_assoc()) {
      $title = $r['title'] ?: ('Run #'.$r['id']);
      echo '<tr class="border-t border-gray-200 dark:border-gray-800">'
          .'<td class="py-2 pr-4"><a class="underline" href="/runs?id='.(int)$r['id'].'">'.htmlspecialchars($title).'</a></td>'
          .'<td class="py-2 pr-4">$'.number_format((float)$r['c'],6).'</td>'
          .'<td class="py-2 pr-4">'.htmlspecialchars((string)$r['created_at']).'</td></tr>';
    }
    echo '</tbody></table></div></div>';

    echo '</div>'; // container

    // Charts JS (Chart.js)
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>';
    echo '<script>
function makeChart(ctxId, type, labels, data){
  const ctx = document.getElementById(ctxId).getContext("2d");
  return new Chart(ctx, {
    type,
    data: {
      labels,
      datasets: [{
        label: "",
        data,
        borderWidth: 2,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins:{legend:{display:false}},
      scales: (type==="pie") ? {} : { y: { beginAtZero: true } }
    }
  });
}
function makeStacked(id, labels, succ, fail){
  const ctx = document.getElementById(id).getContext("2d");
  return new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [
        { label: "Succeeded", data: succ, stack:"stack" },
        { label: "Failed", data: fail, stack:"stack" }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      scales:{ x:{stacked:true}, y:{stacked:true, beginAtZero:true} }
    }
  });
}
makeChart("costChart","line", '.json_encode($labels).', '.json_encode($values).');
makeChart("modelChart","pie", '.json_encode($mlabels).', '.json_encode($mvals).');
makeStacked("sfChart", '.json_encode($slabels).', '.json_encode($svals).', '.json_encode($fvals).');
</script>';
  }

  public function export() {
    $db = \db();
    $format = strtolower($_GET['format'] ?? 'csv');

    $rows = $db->query("
      SELECT id, title, status, model_version, total_tokens, latency_ms, est_cost_usd, created_at, finished_at
      FROM runs
      ORDER BY id DESC
      LIMIT 10000
    ");

    if ($format === 'json') {
      header('Content-Type: application/json');
      $out = [];
      while ($r = $rows->fetch_assoc()) { $out[] = $r; }
      echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
      return;
    }

    // CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="runs_export.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['id','title','status','model','total_tokens','latency_ms','est_cost_usd','created_at','finished_at']);
    while ($r = $rows->fetch_assoc()) {
      fputcsv($f, [
        $r['id'], $r['title'], $r['status'], $r['model_version'],
        $r['total_tokens'], $r['latency_ms'], $r['est_cost_usd'],
        $r['created_at'], $r['finished_at']
      ]);
    }
    fclose($f);
  }
}

// ---- helpers (local to this file) ----
function card(string $label, string $value): string {
  return '<div class="rounded border bg-white dark:bg-gray-900 p-3">'
       . '<div class="text-xs text-gray-600 dark:text-gray-300">'.$label.'</div>'
       . '<div class="font-semibold">'.$value.'</div>'
       . '</div>';
}
function chartBox(string $title, string $id, array $labels, array $values, string $type): string {
  return '<div class="rounded border bg-white dark:bg-gray-900 p-3">'
       . '<div class="text-sm mb-2 text-gray-600 dark:text-gray-300">'.$title.'</div>'
       . '<div class="h-72"><canvas id="'.$id.'"></canvas></div>'
       . '</div>';
}
function stackedBox(string $title, string $id, array $labels, array $succ, array $fail): string {
  return '<div class="rounded border bg-white dark:bg-gray-900 p-3">'
       . '<div class="text-sm mb-2 text-gray-600 dark:text-gray-300">'.$title.'</div>'
       . '<div class="h-80"><canvas id="'.$id.'"></canvas></div>'
       . '</div>';
}

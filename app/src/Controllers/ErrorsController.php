<?php
namespace App\Controllers;

class ErrorsController {
  public function page() {
    $db = \db();
    $rows = $db->query("
      SELECT id, title, status, error_msg, created_at, finished_at
      FROM runs
      WHERE status='failed' OR error_msg IS NOT NULL
      ORDER BY id DESC
      LIMIT 200
    ");

    echo '<!doctype html><meta charset="utf-8">';
    echo '<title>Errors — AI Agent</title>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">';
    include __DIR__ . '/../../public/theme.php';

    echo '<div class="max-w-5xl mx-auto py-10">';
    echo '<div class="flex justify-between items-center mb-6">';
    echo '  <a href="/" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">← Back</a>';
    echo '  <div class="flex gap-2">';
    echo '    <a href="/runs" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">View Runs</a>';
    echo '    <a href="/analytics" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">Analytics</a>';
    echo '    <button data-theme-toggle onclick="toggleTheme()" class="text-sm px-3 py-1 rounded border">Dark Mode</button>';
    echo '  </div>';
    echo '</div>';

    echo '<h1 class="text-2xl font-bold mb-4">Recent Errors</h1>';
    echo '<div class="bg-white dark:bg-gray-900 rounded border">';
    echo '<div class="overflow-x-auto"><table class="w-full text-sm">';
    echo '<thead><tr class="text-left text-gray-600 dark:text-gray-300">'
        .'<th class="py-2 px-3">Run</th><th class="py-2 px-3">Status</th>'
        .'<th class="py-2 px-3">Created</th><th class="py-2 px-3">Finished</th>'
        .'<th class="py-2 px-3">Error</th></tr></thead><tbody>';
    while ($r = $rows->fetch_assoc()) {
      $title = $r['title'] ?: ('Run #'.$r['id']);
      echo '<tr class="border-t border-gray-200 dark:border-gray-800 align-top">';
      echo '<td class="py-2 px-3"><a class="underline" href="/runs?id='.(int)$r['id'].'">'.htmlspecialchars($title).'</a></td>';
      echo '<td class="py-2 px-3">'.htmlspecialchars(ucfirst($r['status'])).'</td>';
      echo '<td class="py-2 px-3">'.htmlspecialchars((string)$r['created_at']).'</td>';
      echo '<td class="py-2 px-3">'.htmlspecialchars((string)$r['finished_at']).'</td>';
      echo '<td class="py-2 px-3"><pre class="whitespace-pre-wrap">'.htmlspecialchars((string)$r['error_msg']).'</pre></td>';
      echo '</tr>';
    }
    if ($rows->num_rows === 0) {
      echo '<tr><td class="py-3 px-3 text-gray-500 dark:text-gray-400" colspan="5">No errors 🎉</td></tr>';
    }
    echo '</tbody></table></div></div>';

    echo '</div>';
  }
}

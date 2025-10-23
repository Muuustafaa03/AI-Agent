<?php
require_once __DIR__ . '/../src/bootstrap.php';
include __DIR__ . '/theme.php';
?>
<!doctype html>
<meta charset="utf-8">
<title>AI Agent 🤖 — Research & Action</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">

<div class="max-w-3xl mx-auto py-10">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">AI Agent 🤖 — Research & Action</h1>
    <div class="flex items-center gap-2">
      <a href="/runs" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">View Runs</a>
      <a href="/analytics" class="text-sm px-3 py-1 rounded border hover:bg-gray-100 dark:hover:bg-gray-800">Analytics</a>
      <button data-theme-toggle onclick="toggleTheme()" class="text-sm px-3 py-1 rounded border">Dark Mode</button>
    </div>
  </div>

  <?php if (isset($_GET['err'])): ?>
    <div class="mb-4 bg-red-600 text-white text-sm px-3 py-2 rounded"><?php echo htmlspecialchars($_GET['err']); ?></div>
  <?php endif; ?>

  <form method="POST" action="/runs" class="bg-white dark:bg-gray-900 p-6 rounded border space-y-4">
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm block mb-1">Input Type</label>
        <select name="input_type" class="w-full border rounded p-2 bg-white dark:bg-gray-950">
          <option value="url">URL</option>
          <option value="text">Raw Text</option>
        </select>
      </div>
      <div>
        <label class="text-sm block mb-1">Template</label>
        <select name="template" class="w-full border rounded p-2 bg-white dark:bg-gray-950">
          <option value="research_brief">Research Brief</option>
          <option value="technical_summary">Technical Summary</option>
          <option value="task_breakdown">Task Breakdown</option>
          <option value="classify_topic">Topic Classification</option>
          <option value="prd_outline">PRD Outline</option>
        </select>
      </div>
    </div>

    <div>
      <label class="text-sm block mb-1">Payload (URL or Text)</label>
      <textarea name="input_payload" rows="6" class="w-full border rounded p-3 font-mono text-sm bg-white dark:bg-gray-950" placeholder="https://example.com/article or paste text here..."></textarea>
    </div>

    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700" type="submit">Run Agent</button>
      <span class="text-xs text-gray-600 dark:text-gray-300">Your OpenAI credits are used only for summarization.</span>
    </div>
  </form>

  <div class="mt-8 text-xs text-gray-500 dark:text-gray-400">
    Tips: URLs behind heavy paywalls may return limited text. You can paste raw text anytime.
  </div>
</div>

<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AI Agent Project 2</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-50">
  <div class="max-w-3xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-6">AI Agent — Research & Action</h1>
    <form class="bg-white p-6 rounded-xl shadow" method="POST" action="/runs">
      <label class="block mb-2 font-semibold">Input Type</label>
      <select name="input_type" class="border rounded p-2 w-full mb-4">
        <option value="url">URL</option>
        <option value="text">Plain Text</option>
      </select>

      <label class="block mb-2 font-semibold">Payload</label>
      <textarea name="input_payload" class="border rounded p-2 w-full mb-4" rows="5" placeholder="Paste a URL or text..."></textarea>

      <label class="block mb-2 font-semibold">Template</label>
      <select name="template" class="border rounded p-2 w-full mb-6">
        <option value="research_brief">Research Brief</option>
        <option value="technical_summary">Technical Summary</option>
        <option value="task_breakdown">Task Breakdown</option>
      </select>

      <button class="bg-black text-white px-4 py-2 rounded-lg">Run Agent</button>
    </form>
    <div class="mt-6">
      <a class="underline" href="/runs">View Runs</a>
    </div>
  </div>
</body>
</html>
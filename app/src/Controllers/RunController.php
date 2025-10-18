<?php
namespace App\Controllers;
use App\Agent\Agent;

class RunController {
  public function create() {
    $inputType = $_POST['input_type'] ?? 'url';
    $payload   = $_POST['input_payload'] ?? '';
    $template  = $_POST['template'] ?? 'research_brief';

    $agent = new Agent();
    $id = $agent->createRun($inputType, $payload);
    $ok = $agent->execute($id, $template);
    header('Location: /runs');
  }

  public function list() {
    $db = \db();
    $res = $db->query("SELECT id, status, input_type, LEFT(input_payload, 100) as preview, created_at FROM runs ORDER BY id DESC LIMIT 50");
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">';
    echo '<div class="max-w-4xl mx-auto py-10"><h1 class="text-2xl font-bold mb-4">Runs</h1><a class="underline" href="/">? Back</a><div class="mt-4 space-y-2">';
    while ($row = $res->fetch_assoc()) {
      echo '<div class="bg-white p-4 rounded shadow flex justify-between">';
      echo '<div><div class="font-semibold">#'.$row['id'].' • '.$row['status'].'</div><div class="text-sm text-gray-600">'.htmlspecialchars($row['input_type']).' — '.htmlspecialchars($row['preview']).'</div></div>';
      echo '</div>';
    }
    echo '</div></div>';
  }
}
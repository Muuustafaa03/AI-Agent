<?php
namespace App\Skills;

class Tools {
  public static function fetch_url(string $url): string {
    $html = @file_get_contents($url);
    if (!$html) return $url; // fallback: treat as text
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML401, 'UTF-8');
    return substr($text, 0, 16000); // cap
  }

  public static function summarize(string $text, string $style='brief'): string {
    $prompt = "Summarize the following text in a {$style} style with bullet highlights:\n\n" . $text;
    return self::openai_simple($prompt);
  }

  public static function task_breakdown(string $text): string {
    $prompt = "From the text below, produce a JSON array of tasks [{title, description, priority (P1|P2|P3)}]:\n\n" . $text;
    return self::openai_simple($prompt);
  }

  private static function openai_simple(string $prompt): string {
    $apiKey = env('OPENAI_API_KEY', '');
    if (!$apiKey) return "[OpenAI key missing]";

    $body = [
      "model" => "gpt-4o-mini",
      "messages" => [
        ["role"=>"system","content"=>"You are a concise assistant."],
        ["role"=>"user","content"=>$prompt]
      ],
      "temperature" => 0.3
    ];
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
      ],
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => json_encode($body)
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) return "[OpenAI request failed]";
    $data = json_decode($resp, true);
    return $data['choices'][0]['message']['content'] ?? "[No content]";
  }
}
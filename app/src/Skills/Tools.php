<?php
namespace App\Skills;

use App\Util\Http;

class Tools {

  // very small HTML → text helper
  private static function html_to_text(string $html): string {
    // remove script/style
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', ' ', $html);
    // strip tags
    $text = strip_tags($html);
    // normalize whitespace
    $text = preg_replace('/[ \t\x0B\f\r]+/u', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", trim($text));
    return $text;
  }

  public static function fetch_title(string $url): ?string {
    $res = Http::get($url, ['timeout'=>15, 'retries'=>1]);
    if (!$res['ok']) return null;
    if (str_contains(Http::contentType($res['headers']), 'html')) {
      if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $res['body'], $m)) {
        return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
      }
    }
    return null;
  }

  public static function fetch_url(string $url): string {
    $res = Http::get($url, ['timeout'=>20, 'retries'=>2]);
    if (!$res['ok']) {
      throw new \RuntimeException("fetch failed: {$res['error']}");
    }
    $ct = Http::contentType($res['headers']);
    if ($ct === '' || str_contains($ct, 'html')) {
      return self::html_to_text($res['body']);
    }
    if (str_contains($ct, 'text/plain') || str_contains($ct, 'json') || str_contains($ct,'xml')) {
      return $res['body'];
    }
    // unsupported
    throw new \RuntimeException("unsupported content-type: {$ct}");
  }

  public static function task_breakdown(string $text): array {
    // super-light MVP task list (heuristic)
    $sentences = preg_split('/(?<=[\.\!\?])\s+/', trim($text));
    $tasks = [];
    foreach ($sentences as $s) {
      $s = trim($s);
      if ($s === '') continue;
      $tasks[] = $s;
      if (count($tasks) >= 12) break;
    }
    return $tasks;
  }

  // Summarize MVP — keep your existing implementation if you have one.
  // We’ll keep a simple heuristic that returns bullet points; Agent will
  // call the Python worker for token/cost/latency estimation.
  public static function summarize(string $text, string $style='brief'): string {
    $lines = preg_split('/\r?\n/', trim($text));
    $bullets = [];
    foreach ($lines as $ln) {
      $ln = trim($ln);
      if ($ln === '') continue;
      $bullets[] = '- ' . $ln;
      if (count($bullets) >= 24) break;
    }
    if (empty($bullets)) $bullets = ['- (no extractable content)'];
    if ($style === 'technical') {
      array_unshift($bullets, '**Technical Summary**');
    } else {
      array_unshift($bullets, '**Research Brief**');
    }
    return implode("\n", $bullets);
  }
}

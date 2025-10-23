<?php
namespace App\Util;

class Http {
  public static function get(string $url, array $opts = []): array {
    $defaultUa = 'ai-agent/1.0 (+https://localhost) curl-like';
    $ua   = $opts['user_agent'] ?? $defaultUa;
    $cto  = (int)($opts['connect_timeout'] ?? 10);
    $rto  = (int)($opts['timeout'] ?? 20);
    $max  = (int)($opts['max_redirs'] ?? 5);
    $tries= (int)($opts['retries'] ?? 2);
    $headersOut = [];

    // simple host blocklist via .env: FETCH_BLOCKLIST=example.com,badsite.tld
    $bl = array_filter(array_map('trim', explode(',', (string)(\env('FETCH_BLOCKLIST','')))));
    $host = parse_url($url, PHP_URL_HOST);
    if ($host && in_array($host, $bl, true)) {
      return ['ok'=>false,'status'=>0,'headers'=>[],'body'=>'','error'=>"Blocked host: {$host}"];
    }

    $err  = '';
    for ($i=0; $i <= $tries; $i++) {
      $ch = curl_init($url);
      $headersOut = [];
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => $max,
        CURLOPT_CONNECTTIMEOUT => $cto,
        CURLOPT_TIMEOUT        => $rto,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_ENCODING       => '', // accept gzip/deflate
        CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$headersOut) {
          $len = strlen($header);
          $parts = explode(':', $header, 2);
          if (count($parts) === 2) {
            $key = strtolower(trim($parts[0]));
            $val = trim($parts[1]);
            $headersOut[$key] = $val;
          }
          return $len;
        },
      ]);
      $body   = curl_exec($ch);
      $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
      $cerr   = curl_error($ch);
      curl_close($ch);

      if ($body === false) { $err = $cerr ?: 'curl error'; }
      else if ($status >= 200 && $status < 300) {
        // Cap very large responses (3MB)
        if (strlen($body) > 3*1024*1024) $body = substr($body, 0, 3*1024*1024);
        return ['ok'=>true,'status'=>$status,'headers'=>$headersOut,'body'=>$body,'error'=>''];
      } else if (in_array($status, [403, 429, 503], true)) {
        // transient-ish: try backing off
        usleep((int) (pow(2, $i) * 250_000)); // 250ms, 500ms, 1s...
      } else {
        $err = "HTTP {$status}";
        break;
      }
    }
    return ['ok'=>false,'status'=>$status ?? 0,'headers'=>$headersOut,'body'=>'','error'=>$err];
  }

  public static function contentType(array $headers): string {
    foreach (['content-type','Content-Type'] as $k) {
      if (isset($headers[$k])) return strtolower(trim(explode(';',$headers[$k])[0]));
    }
    return '';
  }
}

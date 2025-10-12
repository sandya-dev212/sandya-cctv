<?php namespace App\Libraries;

class Shinobi
{
    private function sanitizeBaseUrl(string $baseUrl): string
    {
        $u = trim($baseUrl);
        if ($u === '') return '';
        if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u; // asumsi HTTPS
        return rtrim($u, '/');
    }

    protected function httpGet(string $url, int $timeout=8): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout + 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // self-signed oke
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) return ['ok'=>false, 'code'=>$code, 'error'=>$err, 'data'=>null];
        $json = json_decode($body, true);
        return ['ok'=>($code>=200 && $code<300), 'code'=>$code, 'error'=>null, 'data'=>$json];
    }

    /** Get Monitors: list all or single */
    public function getMonitors(string $baseUrl, string $apiKey, string $groupKey, ?string $monitorId=null): array
    {
        $b = $this->sanitizeBaseUrl($baseUrl);
        $path = $monitorId
            ? '/monitor/' . rawurlencode($groupKey) . '/' . rawurlencode($monitorId)
            : '/monitor/' . rawurlencode($groupKey);
        $url = $b . '/' . rawurlencode($apiKey) . $path;
        return $this->httpGet($url);
    }

    /** Videos listing (kalau butuh) */
    public function getVideos(string $baseUrl, string $apiKey, string $groupKey, ?string $monitorId=null): array
    {
        $b = $this->sanitizeBaseUrl($baseUrl);
        $path = $monitorId
            ? '/videos/' . rawurlencode($groupKey) . '/' . rawurlencode($monitorId)
            : '/videos/' . rawurlencode($groupKey);
        $url = $b . '/' . rawurlencode($apiKey) . $path;
        return $this->httpGet($url, 12);
    }

    // URL builders (sesuai docs)
    public function hlsUrl(string $baseUrl, string $apiKey, string $groupKey, string $monitorId): string
    {
        $b = $this->sanitizeBaseUrl($baseUrl);
        return $b . '/' . rawurlencode($apiKey) . '/hls/' . rawurlencode($groupKey) . '/' . rawurlencode($monitorId) . '/s.m3u8';
    }
    public function jpegUrl(string $baseUrl, string $apiKey, string $groupKey, string $monitorId): string
    {
        $b = $this->sanitizeBaseUrl($baseUrl);
        return $b . '/' . rawurlencode($apiKey) . '/jpeg/' . rawurlencode($groupKey) . '/' . rawurlencode($monitorId) . '/s.jpg';
    }

    /** Normalisasi output getMonitors ke bentuk simpel */
    public function normalizeMonitors(mixed $data): array
    {
        $out = [];
        if (!is_array($data)) return $out;

        // Ada instance yang balikin array keyed-by MID, ada juga list numerik
        foreach ($data as $k => $v) {
            $mon = (is_array($v) && isset($v['monitor'])) ? $v['monitor'] : $v;

            // mid bisa di 'mid' | 'id' | 'monitor_id' | key array
            $mid = $mon['mid'] ?? $mon['id'] ?? $mon['monitor_id'] ?? (is_string($k) ? $k : null);
            if (!$mid) continue;

            $name = $mon['name'] ?? $mon['alias'] ?? $mon['monitor_name'] ?? $mid;

            $out[] = [
                'mid'    => (string)$mid,
                'name'   => (string)$name,
                'detail' => $mon, // keep raw kalau perlu
            ];
        }
        return $out;
    }
}

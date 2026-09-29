<?php
// Shared UnityEdge auth helpers: refresh expired supabase JWT + create-code.
const UNET_APIKEY = 'sb_publishable_yKqi0fu5vV6G4ryUIMJuzw_NCoFEl1c';
const UNET_CREATE_CODE_URL = 'https://auth.unityedge.io/api/auth/create-code';
const UNET_CREATE_CODE_KEY = 'unet-sharable:aIa_p693uDWFJLrfwsR0za53CG05UgpIB_hzTFc61I4';

function unet_http_post($url, $headers, $body, $timeout = 10) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($resp === false) return [$http ?: 0, '', $err];
    return [$http, $resp, null];
}

// Try refresh_token -> new access token. Returns [access, refresh] or [null, null].
function unet_refresh_session($refresh) {
    $hosts = [
        'https://api.unityedge.io/auth/v1/token?grant_type=refresh_token',
        'https://vtllpagtmncbkywsqccd.supabase.co/auth/v1/token?grant_type=refresh_token',
    ];
    $hdrs = [
        'apikey: ' . UNET_APIKEY,
        'content-type: application/json',
        'x-client-info: supabase-js-web/2.87.1',
        'origin: https://scoutandrunner.com',
        'referer: https://scoutandrunner.com/',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
    ];
    foreach ($hosts as $url) {
        list($http, $resp) = unet_http_post($url, $hdrs, json_encode(['refresh_token' => $refresh]), 10);
        if ($http === 200) {
            $j = json_decode($resp, true);
            if (is_array($j) && !empty($j['access_token'])) {
                return [$j['access_token'], $j['refresh_token'] ?? $refresh];
            }
        }
    }
    return [null, null];
}

function unet_create_code($supa, $lic, $email) {
    $hdrs = [
        'Authorization: Bearer ' . $supa,
        'Content-Type: application/json',
        'X-Unity-Api-Key: ' . UNET_CREATE_CODE_KEY,
        'Origin: https://scoutandrunner.com',
        'Referer: https://scoutandrunner.com/',
    ];
    $body = json_encode(['supabaseToken' => $supa, 'licenseId' => $lic, 'email' => $email]);
    list($http, $resp) = unet_http_post(UNET_CREATE_CODE_URL, $hdrs, $body, 10);
    $j = json_decode($resp, true);
    $code = is_array($j) ? ($j['code'] ?? $j['data']['code'] ?? null) : null;
    return [$code, $http, $resp];
}

function unet_resp_expired($resp, $http) {
    if ($http === 401) return true;
    $s = (string)$resp;
    return stripos($s, 'token is expired') !== false || stripos($s, 'jwt expired') !== false;
}

// Full create-code flow with one refresh+retry. Loads capture, refreshes if stale,
// persists new tokens, returns [code, http, resp] or [null, http, resp].
function unet_fresh_code_for_bot($pdo, $bot_id, $email) {
    $lc = $pdo->prepare("SELECT supabase_token, license_id, refresh_token FROM bot_license_capture WHERE bot_id=:id");
    $lc->execute([':id' => (int)$bot_id]);
    $lr = $lc->fetch();
    if (!$lr || !$lr['supabase_token'] || !$lr['license_id']) {
        return [null, 0, 'no capture', null, null, null];
    }
    $supa = $lr['supabase_token'];
    $lic = $lr['license_id'];
    $refresh = $lr['refresh_token'] ?? null;

    list($code, $http, $resp) = unet_create_code($supa, $lic, $email);
    $note = null;

    if ((!$code || unet_resp_expired($resp, $http))) {
        if (!$refresh) {
            $note = 'no refresh_token captured';
        } else {
            list($newTok, $newRef) = unet_refresh_session($refresh);
            if ($newTok) {
                try {
                    $pdo->prepare("UPDATE bot_license_capture SET supabase_token=:t, refresh_token=:r, updated_at=NOW() WHERE bot_id=:id")
                        ->execute([':t' => $newTok, ':r' => $newRef, ':id' => (int)$bot_id]);
                } catch (Exception $e) {}
                $supa = $newTok;
                $note = 'refreshed ok';
                list($code, $http, $resp) = unet_create_code($supa, $lic, $email);
            } else {
                $note = 'refresh_token rejected';
            }
        }
    }
    return [$code, $http, $resp, $supa, $lic, $note];
}

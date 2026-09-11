<?php
/**
 * relay_stafflist.php（GET用の中継・橋渡し）
 *
 * ポータルサイト（tablet_setup.php）が、VetssMS側の staff_list.php を
 * サーバー間で（PHPの file_get_contents 等で）直接呼べるなら、
 * 本来この中継は不要です。
 *
 * ただし、もし「ブラウザから直接fetchする」形にしたい場合や、
 * ポータル側から133.18.242.109へ直接アクセスできない事情がある場合は、
 * このファイルを vetss-karte.net（https）側に置き、代わりに呼び出してください。
 *
 * quickmemo/relay.php（POST用）のGET版です。
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$TARGET_URL = "http://133.18.242.109/chat_kww/staff_list.php";

$token = $_GET['token'] ?? '';
$url = $TARGET_URL . '?token=' . urlencode($token);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; OpenVetss-Relay/1.0)");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['success'=>false, 'message'=>'転送先への接続に失敗しました：' . $curlErr], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code($httpCode ?: 200);
echo $response;

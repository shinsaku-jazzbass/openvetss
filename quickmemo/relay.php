<?php
/**
 * relay.php（中継・橋渡し用）
 *
 * clinical_quickmemo.html（https://www.vetss-karte.net/…）から見て、
 * 転送先の quickmemo_import.php が http:// のままだと、ブラウザの
 * 「Mixed Content」規制でブロックされてしまう問題への、当面の回避策。
 *
 * このファイル自体は vetss-karte.net（https）側に置き、
 * ブラウザからは常にこのファイル（https）宛てに送信する。
 * ここから先、http://133.18.242.109 への転送はサーバー同士の通信になるため、
 * ブラウザのMixed Content規制の対象外になる。
 *
 * 【本来の解決策】133.18.242.109側がHTTPS化できたら、このファイルは不要になる。
 * その時は、clinical_quickmemo.html側の「転送先URL」設定を、
 * 直接 https://133.18.242.109/... に向け直すだけでよい。
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false, 'message'=>'POSTのみ対応しています']);
    exit;
}

// ==== 転送先（本物の quickmemo_import.php） ====
$TARGET_URL = "http://133.18.242.109/chat_kww/quickmemo_import.php";

// ==== 受け取ったPOSTの中身（token・petId・krtId・kubun・text・clientMemoId・images）を
//      そのまま転送先へ渡す。中継役はトークンの中身を見ず、素通しするだけ。 ====
$postFields = $_POST;

$ch = curl_init($TARGET_URL);
curl_setopt($ch, CURLOPT_POST, true);
// 配列のまま渡すと、PHPのcurlは自動的に multipart/form-data 形式にしてしまう。
// 手動のcurlコマンド（-d "..."）と同じ application/x-www-form-urlencoded 形式に
// 揃えるため、文字列に変換してから渡す（multipart形式が、先方のセキュリティ設定に
// 引っかかっている可能性への対処）。
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 転送先がhttpなので実質無関係だが念のため
// 手動のcurlコマンドと発信元IPが一致するよう、IPv4を強制する
// （IPv6側が許可リストに無く、そちらで弾かれている可能性への対処）
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
// PHPのcurl_execは既定でUser-Agentを一切付けない。手動のcurlコマンドは
// 自動で "curl/バージョン" を付けているため、ここに差がある。
// User-Agentが無いリクエストを機械的にブロックするセキュリティ設定に
// 該当している可能性への対処として、明示的に付与する。
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; OpenVetss-Relay/1.0)");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['success'=>false, 'message'=>'転送先への接続に失敗しました：' . $curlErr]);
    exit;
}

// 転送先（quickmemo_import.php）からの応答を、そのままブラウザへ返す。
// 403はIP制限だけでなく「トークン不一致」でも正しく返る値なので、
// ここで403かどうかを判定してメッセージを差し替えることはしない。
http_response_code($httpCode ?: 200);
echo $response;

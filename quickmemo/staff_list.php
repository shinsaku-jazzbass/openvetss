<?php
/**
 * staff_list.php
 * 公開API：VetssMSの投稿者候補一覧（読み取り専用・トークン認証つき）
 * GET /chat_kww/staff_list.php?token=xxxxx
 *
 * ポータルサイトの tablet_setup.php が、セットアップ用QR/URLを発行する前に、
 * 「本当にVetssMSに存在するユーザーか」を確認するために呼ぶ。
 * today_reception.php・quickmemo_import.phpと同じ考え方（読み取り専用・書き込みなし）。
 *
 * トークンは quickmemo_import.php・upload_photo.php と同じ値でよい
 * （どれも「VetssMS側への、この病院からのアクセス」を許可する、共通の合言葉のため）。
 */

require_once __DIR__ . "/database_connection.php";

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$TOKEN = "（quickmemo_import.php・upload_photo.phpと同じ値を設定）";
$givenToken = $_GET['token'] ?? '';
if (!hash_equals($TOKEN, $givenToken)) {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'アクセスが許可されていません'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // login_flgは「有効・無効」を示すフラグではなく、実データでは全員0だったため、
    // 絞り込みには使わない（誤って対象者ゼロになる事故を避ける）。全員を対象にする。
    $st = $connect->query("SELECT user_id, nickname FROM login ORDER BY nickname");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $list = array_map(function($r){
        return ['userId' => (int)$r['user_id'], 'name' => $r['nickname']];
    }, $rows);

    echo json_encode(['success'=>true, 'count'=>count($list), 'list'=>$list], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('staff_list.php error: ' . $e->getMessage());
    echo json_encode(['success'=>false, 'message'=>'エラーが発生しました'], JSON_UNESCAPED_UNICODE);
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kirim pesan ke Telegram via Bot API
 * @param  string $bot_token  Token bot dari @BotFather
 * @param  string $chat_id    Chat ID penerima
 * @param  string $text       Pesan (mendukung HTML)
 * @return bool
 */
function telegram_send($bot_token, $chat_id, $text)
{
    if (empty($bot_token) || empty($chat_id)) return FALSE;

    $url  = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';
    $data = http_build_query([
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $data,
            'timeout' => 5,
            'ignore_errors' => TRUE,
        ],
        'ssl' => [
            'verify_peer'      => FALSE,
            'verify_peer_name' => FALSE,
        ],
    ]);

    $result = @file_get_contents($url, FALSE, $ctx);
    if ($result === FALSE) return FALSE;

    $json = json_decode($result, TRUE);
    return isset($json['ok']) && $json['ok'] === TRUE;
}

/**
 * Kirim notifikasi Telegram ke semua Admin Provinsi yang punya chat_id
 * Dipakai dari controller — ambil bot_token dari DB setting
 */
function telegram_notif_admin_prov($message)
{
    $CI =& get_instance();

    // Ambil bot token dari setting
    $setting = $CI->db->get_where('ref_app_setting', ['kode' => 'telegram_bot_token'])->row();
    $token   = $setting ? trim($setting->nilai) : '';
    if (empty($token)) return;

    // Ambil semua admin provinsi + superadmin yang punya chat_id
    $admins = $CI->db
        ->select('u.telegram_chat_id')
        ->from('users u')
        ->join('roles r', 'r.id = u.role_id')
        ->where_in('r.kode', ['superadmin', 'admin_provinsi'])
        ->where('u.is_active', 1)
        ->where('u.telegram_chat_id IS NOT NULL', NULL, FALSE)
        ->where('u.telegram_chat_id !=', '')
        ->get()->result();

    foreach ($admins as $admin) {
        telegram_send($token, $admin->telegram_chat_id, $message);
    }
}

<?php

$token = "8082842866:AAF0A5f3Tq6oflGP488WNnFBsxR8g03mJ8o";
define("API_URL", "https://api.telegram.org/bot$token/");

$update = json_decode(file_get_contents("php://input"), true);
$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text = $message["text"];
$data = $update["callback_query"]["data"] ?? null;
$callback_chat_id = $update["callback_query"]["message"]["chat"]["id"] ?? null;
$callback_message_id = $update["callback_query"]["message"]["message_id"] ?? null;

function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "Markdown",
    ];
    if ($reply_markup) {
        $data["reply_markup"] = json_encode($reply_markup);
    }
    file_get_contents(API_URL . "sendMessage?" . http_build_query($data));
}

function editMessage($chat_id, $message_id, $text, $reply_markup = null) {
    $data = [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $text,
        "parse_mode" => "Markdown"
    ];
    if ($reply_markup) {
        $data["reply_markup"] = json_encode($reply_markup);
    }
    file_get_contents(API_URL . "editMessageText?" . http_build_query($data));
}

// Foydalanuvchi tillarini saqlash uchun
$user_lang_file = "users_lang.json";
$user_wallet_file = "users_wallet.json";

$users_lang = file_exists($user_lang_file) ? json_decode(file_get_contents($user_lang_file), true) : [];
$users_wallet = file_exists($user_wallet_file) ? json_decode(file_get_contents($user_wallet_file), true) : [];

if ($text == "/start") {
    $keyboard = [
        "inline_keyboard" => [
            [["text" => "🇺🇿 O'zbekcha", "callback_data" => "lang_uz"]],
            [["text" => "🇷🇺 Русский", "callback_data" => "lang_ru"]]
        ]
    ];
    sendMessage($chat_id, "Tilni tanlang / Выберите язык:", $keyboard);
}

if ($data == "lang_uz" || $data == "lang_ru") {
    $lang = ($data == "lang_uz") ? "uz" : "ru";
    $users_lang[$callback_chat_id] = $lang;
    file_put_contents($user_lang_file, json_encode($users_lang));
    
    $keyboard = [
        "inline_keyboard" => [
            [["text" => "🔄 Valyuta Ayirboshlash", "callback_data" => "exchange"]],
            [["text" => "📞 Aloqa", "url" => "https://t.me/Uznex_org"]],
            [["text" => "❓ Yordam", "callback_data" => "help"]]
        ]
    ];
    $text = ($lang == "uz") ? "Asosiy menyu:" : "Главное меню:";
    editMessage($callback_chat_id, $callback_message_id, $text, $keyboard);
}

if ($data == "exchange") {
    $keyboard = [
        "inline_keyboard" => [
            [["text" => "💸 Berish", "callback_data" => "give"]],
            [["text" => "💰 Olish", "callback_data" => "receive"]],
            [["text" => "🔙 Orqaga", "callback_data" => "back_main"]]
        ]
    ];
    editMessage($callback_chat_id, $callback_message_id, "Qaysi amalni bajarmoqchisiz?", $keyboard);
}

if ($data == "give" || $data == "receive") {
    $action = ($data == "give") ? "bermoqchisiz" : "olmoqchisiz";
    $keyboard = [
        "inline_keyboard" => [
            [["text" => "🇺🇿 UZS", "callback_data" => $data."_UZS"]],
            [["text" => "💵 USDT (TRC20)", "callback_data" => $data."_USDT"]],
            [["text" => "🔷 TRON (TRX)", "callback_data" => $data."_TRX"]],
            [["text" => "🔹 TON", "callback_data" => $data."_TON"]],
            [["text" => "💳 PAYEER", "callback_data" => $data."_PAYEER"]],
            [["text" => "🔙 Orqaga", "callback_data" => "exchange"]]
        ]
    ];
    editMessage($callback_chat_id, $callback_message_id, "Qaysi valyutani $action?", $keyboard);
}

if (preg_match("/^(give|receive)_(UZS|USDT|TRX|TON|PAYEER)$/", $data, $m)) {
    $type = $m[1];
    $currency = $m[2];

    $admin_wallets = [
        "UZS" => "9860 1301 0231 7737",
        "USDT" => "TNq5Ldq61rHVWypAJLW1Ma3k1CSNSiuXpJ",
        "TRX" => "TNq5Ldq61rHVWypAJLW1Ma3k1CSNSiuXpJ",
        "TON" => "UQBSBlb-WSGe7qF8SSZig0VCl8SzQFCyoVlc1EULPTIAp1UC",
        "PAYEER" => "P1087356014"
    ];

    $wallet = $admin_wallets[$currency];
    $text = "Siz *$currency* $type tanladingiz.\n";
    $text .= "Iltimos, miqdorni kiriting va quyidagi hamyonga to'lovni amalga oshiring:\n\n";
    $text .= "`$wallet`\n\n";
    $text .= "Shuningdek, 'Hamyon' bo'limiga o'zingizning hamyon manzilingizni kiriting.";

    editMessage($callback_chat_id, $callback_message_id, $text);
}

if ($data == "help") {
    editMessage($callback_chat_id, $callback_message_id, "❓ Yordam:\n1. Bot orqali valyuta ayirboshlang.\n2. Miqdor kiriting.\n3. Bizning hamyonimizga to‘lov qiling.\n4. Admin tekshiradi va sizga yuboradi.");
}

if ($data == "back_main") {
    $keyboard = [
        "inline_keyboard" => [
            [["text" => "🔄 Valyuta Ayirboshlash", "callback_data" => "exchange"]],
            [["text" => "📞 Aloqa", "url" => "https://t.me/Uznex_org"]],
            [["text" => "❓ Yordam", "callback_data" => "help"]]
        ]
    ];
    $lang = $users_lang[$callback_chat_id] ?? "uz";
    $text = ($lang == "uz") ? "Asosiy menyu:" : "Главное меню:";
    editMessage($callback_chat_id, $callback_message_id, $text, $keyboard);
}

// Hamyon qo'shish
if ($text == "/hamyon") {
    sendMessage($chat_id, "Iltimos, sizga tegishli hamyon manzilingizni kiriting:");
    $users_wallet[$chat_id] = "awaiting";
    file_put_contents($user_wallet_file, json_encode($users_wallet));
} elseif ($users_wallet[$chat_id] == "awaiting") {
    $users_wallet[$chat_id] = $text;
    file_put_contents($user_wallet_file, json_encode($users_wallet));
    sendMessage($chat_id, "✅ Hamyon manzilingiz saqlandi:\n`$text`");
}

?>

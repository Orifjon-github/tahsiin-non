<?php

namespace App\Helpers;

use App\Models\UsersTransfers;

class MainHelper
{
    public static function formatter(string $key, string $value): string
    {
        $key_len = mb_strlen($key);
        $value_len = mb_strlen($value);
        if (($key_len + $value_len) < 29) {
            $need = 29 - ($key_len + $value_len);
            $dot = '.';
            for ($i = 2; $i <= $need; $i++) {
                $dot .= '.';
            }
            return "<code>$key</code> <code>$dot</code> <code>$value</code>";
        } else {
            return "<code>$key</code> - <code>$value</code>";
        }
    }

    public static function makeMessage($utid): string
    {
        $transfer = UsersTransfers::where('utid', $utid)->first();
        if (!$transfer) {
            return 'Transfer not found. Connect with Admins';
        }

        if ($transfer->status != 'accept') {
            return 'Transfer not accepted, status error. Connect with Admins';
        }
        $datetime = date('d-m-Y H:i', strtotime($transfer->date)); // Sana formatini o'zgartirish
        $params = explode('*', $transfer->desc);
        $name = $params[0] ?? 'Topilmadi...';
        $phone = $params[1] ?? 'Topilmadi...';

// Telefon raqamni formatlash
        $formattedPhone = preg_replace('/(\d{2})(\d{3})(\d{2})(\d{2})/', '+998 ($1) $2-$3-$4', $phone);

        return "<b>✅ Yangi to'lov</b>\n\n" .
            "🆔 <b>Tranzaksiya ID:</b> <i>{$transfer->utid}</i>\n" .
            "💰 <b>To‘lov summasi:</b> <i>{$transfer->amount} UZS</i>\n" .
            "👤 <b>To‘lovchini Ismi:</b> <i>{$name}</i>\n" .
            "📱 <b>Telefon raqam:</b> <i>{$formattedPhone}</i>\n" .
            "📅 <b>Sana:</b> <i>{$datetime}</i>\n\n" .
            "🔍 Qo‘shimcha ma’lumot kerak bo‘lsa, biz bilan bog‘laning!\n" .
            "📞 <b>Aloqa markazi:</b> +998 78 148 00 10";

    }
}

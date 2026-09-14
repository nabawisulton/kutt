<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Member cards + QR verification tokens.
 */
final class Card
{
    /** Issue (or re-issue) a card for a member. */
    public static function issue(int $memberId, string $memberNo): array
    {
        $existing = Database::first(
            'SELECT * FROM member_cards WHERE member_id = ? AND is_active = 1',
            [$memberId]
        );
        if ($existing !== null) {
            return $existing;
        }

        $verifyCode = bin2hex(random_bytes(16)); // 32 hex chars, secret token
        Database::insert('member_cards', [
            'member_id'    => $memberId,
            'card_serial'  => 'KRT-' . date('Y') . '-' . str_pad((string) $memberId, 5, '0', STR_PAD_LEFT),
            'verify_code'  => $verifyCode,
            'issued_by'    => \App\Core\Auth::id(),
            'is_active'    => 1,
        ]);

        return Database::first(
            'SELECT * FROM member_cards WHERE member_id = ? AND is_active = 1',
            [$memberId]
        ) ?? [];
    }

    public static function forMember(int $memberId): ?array
    {
        return Database::first(
            'SELECT * FROM member_cards WHERE member_id = ? AND is_active = 1',
            [$memberId]
        );
    }

    /** Resolve a QR token to a member id (null when invalid). */
    public static function memberByToken(string $token): ?array
    {
        $card = Database::first(
            'SELECT mc.member_id FROM member_cards mc
             JOIN members m ON m.id = mc.member_id
             WHERE mc.verify_code = ? AND mc.is_active = 1',
            [$token]
        );

        return $card === null ? null : Member::find((int) $card['member_id']);
    }

    public static function rotateToken(int $memberId): string
    {
        $token = bin2hex(random_bytes(16));
        Database::exec(
            'UPDATE member_cards SET verify_code = ? WHERE member_id = ? AND is_active = 1',
            [$token, $memberId]
        );

        return $token;
    }

    // --- Card background settings (depan & belakang terpisah) ---------------

    /** @return array{type:string,color:string,image:?string,opacity:float} */
    public static function backgroundSettings(): array
    {
        return [
            'type'    => Setting::get('card_bg_type', 'color') ?? 'color',
            'color'   => Setting::get('card_bg_color', '#0b7a3e') ?? '#0b7a3e',
            'image'   => Setting::get('card_bg_image', '') ?: null,
            'opacity' => (float) (Setting::get('card_bg_opacity', '0.25') ?? 0.25),
        ];
    }

    /** Background khusus sisi belakang kartu (fallback: ikut sisi depan). */
    /** @return array{type:string,color:string,image:?string,opacity:float}|null */
    public static function backBackgroundSettings(): ?array
    {
        if (Setting::get('card_back_enabled', '0') !== '1') {
            return null; // belakang memakai background yang sama dengan depan
        }

        return [
            'type'    => Setting::get('card_back_bg_type', 'color') ?? 'color',
            'color'   => Setting::get('card_back_bg_color', '#052e1a') ?? '#052e1a',
            'image'   => Setting::get('card_back_bg_image', '') ?: null,
            'opacity' => (float) (Setting::get('card_back_bg_opacity', '0.35') ?? 0.35),
        ];
    }

    /** @param array<string,string> $values */
    public static function saveBackgroundSettings(array $values, ?int $userId): void
    {
        foreach ($values as $key => $value) {
            Setting::set($key, $value, $userId);
        }
    }
}

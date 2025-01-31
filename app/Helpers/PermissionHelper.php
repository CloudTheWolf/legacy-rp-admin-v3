<?php

namespace App\Helpers;

use App\Player;
use Illuminate\Http\Request;

class PermissionHelper
{
    const PERMISSIONS = [
        self::PERM_SCREENSHOT => ['screenshot', self::LEVEL_STAFF],
        self::PERM_SUSPICIOUS => ['suspicious', self::LEVEL_TRUSTED],
        self::PERM_ADVANCED   => ['advanced', self::LEVEL_TRUSTED],
        self::PERM_LIVEMAP    => ['livemap', self::LEVEL_STAFF],
        self::PERM_LOCK_BAN   => ['lock_ban', self::LEVEL_TRUSTED],
        self::PERM_SOFT_BAN   => ['soft_ban', self::LEVEL_ROOT],
        self::PERM_EDIT_TAG   => ['edit_tag', self::LEVEL_ROOT],
    ];

    const PERM_SCREENSHOT = 'P_SCREENSHOT';
    const PERM_SUSPICIOUS = 'P_SUSPICIOUS';
    const PERM_ADVANCED   = 'P_ADVANCED';
    const PERM_LIVEMAP    = 'P_LIVEMAP';
    const PERM_LOCK_BAN   = 'P_LOCK_BAN';
    const PERM_SOFT_BAN   = 'P_SOFT_BAN';
    const PERM_EDIT_TAG   = 'P_EDIT_TAG';

    const LEVEL_STAFF      = 1;
    const LEVEL_TRUSTED    = 2;
    const LEVEL_SUPERADMIN = 3;
    const LEVEL_ROOT       = 4;

    public static function getFrontendPermissions(): array
    {
        $permissions = [];

        foreach (self::PERMISSIONS as $key => $label) {
            $permissions[$label[0]] = self::getPermissionLevel($key);
        }

        return $permissions;
    }

    private static function getPermissionLevel(string $key): int
    {
        $level = strtolower(env($key, null));

        switch ($level) {
            case 'root':
                return self::LEVEL_ROOT;
            case 'superadmin':
                return self::LEVEL_SUPERADMIN;
            case 'trusted':
                return self::LEVEL_TRUSTED;
            case 'staff':
                return self::LEVEL_STAFF;
        }

        return self::PERMISSIONS[$key][1];
    }

    public static function hasPermission(Request $request, string $key): bool
    {
        $player = $request->user();
        if (!$player) {
            return false;
        }

        $player = $player->player;

        if (!isset(self::PERMISSIONS[$key])) {
            return true;
        }

        $level = 0;

        if (GeneralHelper::isUserRoot($player->steam_identifier)) {
            $level = self::LEVEL_ROOT;
        } else if ($player->is_super_admin) {
            $level = self::LEVEL_SUPERADMIN;
        } else if ($player->is_panel_trusted) {
            $level = self::LEVEL_TRUSTED;
        } else if ($player->is_staff) {
            $level = self::LEVEL_STAFF;
        }

        return self::getPermissionLevel($key) <= $level;
    }
}

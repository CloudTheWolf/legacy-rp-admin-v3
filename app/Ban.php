<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * A ban that can be issued by a player and received by a players.
 *
 * @package App
 */
class Ban extends Model
{
    use HasFactory;

    /**
     * Column name for when the model was created.
     */
    const CREATED_AT = 'timestamp';

    /**
     * Column name for when the model was last updated.
     */
    const UPDATED_AT = 'timestamp';

    /**
     * @var array
     */
    protected static $bans = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_bans';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ban_hash',
        'identifier',
        'smurf_account',
        'creator_name',
        'creator_identifier',
        'reason',
        'timestamp',
        'expire',
        'locked',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'locked' => 'boolean',
        'timestamp' => 'datetime',
    ];

    /**
     * Gets the date that the ban expires.
     *
     * @return Carbon
     */
    public function getExpireAtAttribute(): ?Carbon
    {
        if (is_null($this->expire)) {
            return null;
        }
        return Date::createFromTimestamp($this->timestamp->getTimestamp() + $this->expire);
    }

    public function getExpireTimeInSeconds(): ?int
    {
        return $this->expire;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp->getTimestamp();
    }

    /**
     * Checks if the ban has expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        return is_null($this->expireAt)
            ? false
            : $this->expireAt->isPast();
    }

    /**
     * Gets the player relationship.
     *
     * @return BelongsTo
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'steam_identifier', 'identifier');
    }

    /**
     * Gets the issuer relationship.
     *
     * @return BelongsTo
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'creator_name', 'player_name');
    }

    /**
     * Returns a formatted reason if the ban was automated
     *
     * @return string
     */
    public function getFormattedReason(): string
    {
        if ($this->creator_name) {
            return $this->reason ?? '';
        }

        $reasons = json_decode(file_get_contents(__DIR__ . '/../helpers/automated-bans.json'), true);
        $parts = explode('-', $this->reason ?? '');

        $category = array_shift($parts);
        $key = array_shift($parts);

        if ($reasons && $category && $key && isset($reasons[$category]) && isset($reasons[$category][$key])) {
            $reason = $reasons[$category][$key];

            return str_replace('${DATA}', implode('-', $parts), $reason);
        }
        return $this->reason ?? '';
    }

    public static function getBanForUser(string $steamIdentifier): ?array
    {
        if (empty(self::$bans)) {
            $ban = Ban::query()
                ->where('identifier', '=', $steamIdentifier)
                ->select(['id', 'ban_hash', 'identifier', 'creator_name', 'reason', 'timestamp', 'expire', 'creator_identifier', 'locked'])
                ->first();
            return $ban ? $ban->toArray() : null;
        }

        return self::$bans[$steamIdentifier] ?? null;
    }

    /**
     * Returns all banned Steam Identifiers which were banned by a certain person
     *
     * @param string $creatorName
     * @param string $creatorIdentifier
     * @return array
     */
    public static function getAllBannedIdentifiersByCreator(string $creatorName, string $creatorIdentifier): array
    {
        $bans = self::getAllBans(false);

        return array_values(array_map(function ($ban) {
            return $ban['identifier'];
        }, array_filter($bans, function ($ban) use ($creatorName, $creatorIdentifier) {
            return $ban['creator_name'] === $creatorName || $ban['creator_identifier'] === $creatorIdentifier;
        })));
    }

    public static function getAllBans(bool $returnOnlyIdentifiers, ?array $filterByIdentifiers = null, bool $forceObject = false): array
    {
        if (empty(self::$bans)) {
            $query = Ban::query()
                ->select(['id', 'ban_hash', 'identifier', 'creator_name', 'reason', 'timestamp', 'expire', 'creator_identifier']);

            if ($filterByIdentifiers === null) {
                $query->where('identifier', 'LIKE', 'steam:%');
            } else {
                $query->whereIn('identifier', $filterByIdentifiers);
            }

            $bans = $query->orderBy('timestamp')
                ->groupBy('identifier')
                ->get()->toArray();

            foreach ($bans as $ban) {
                self::$bans[$ban['identifier']] = $ban;
            }
        }

        $bans = self::$bans;
        if ($filterByIdentifiers !== null) {
            $bans = array_filter($bans, function ($ban) use ($filterByIdentifiers) {
                return in_array($ban['identifier'], $filterByIdentifiers);
            });
        }

        if ($returnOnlyIdentifiers) {
            return array_keys($bans);
        }

        if ($forceObject && empty($bans)) {
            return ['empty' => 'object'];
        }

        return $bans;
    }
}

<?php

namespace App\Models;

use App\Support\LegacyImport\LegacyFontTagParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class Room extends Model
{
    protected $fillable = [
        'legacy_grouptopic_id',
        'slug',
        'name',
        'name_color',
        'name_en',
        'description',
        'access_level',
        'sort_order',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function latestTopic(): HasOne
    {
        return $this->hasOne(Topic::class)->latestOfMany('last_posted_at');
    }

    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_moderators');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $maxLevel = $user?->effectiveRoomAccessLevel() ?? 0;

        return $query->where('access_level', '<=', $maxLevel);
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->access_level <= ($user?->effectiveRoomAccessLevel() ?? 0);
    }

    public function coloredNameHtml(): HtmlString
    {
        $name = e($this->name);
        $badge = $this->accessLevelBadgeHtml()->toHtml();

        if ($this->name_color === null) {
            return new HtmlString($badge.$name);
        }

        $color = e((string) $this->name_color);

        return new HtmlString($badge.'<span style="color: '.$color.'">'.$name.'</span>');
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'en') {
            $englishName = trim((string) $this->name_en);

            if ($englishName !== '') {
                return $englishName;
            }
        }

        return trim((string) $this->name);
    }

    public function hasRecentActivity(): bool
    {
        if ($this->relationLoaded('latestTopic')) {
            return $this->latestTopic?->isNewlyPosted() ?? false;
        }

        $threshold = now()->subDays((int) config('peoplecine.new_post_days', 3));

        return $this->topics()
            ->where('last_posted_at', '>=', $threshold)
            ->exists();
    }

    public function coloredLocalizedNameHtml(?string $locale = null): HtmlString
    {
        $name = e($this->localizedName($locale));
        $badge = $this->accessLevelBadgeHtml($locale)->toHtml();

        if ($this->name_color === null) {
            return new HtmlString($badge.$name);
        }

        $color = e((string) $this->name_color);

        return new HtmlString($badge.'<span style="color: '.$color.'">'.$name.'</span>');
    }

    public function accessLevelBadgeHtml(?string $locale = null): HtmlString
    {
        $level = max(0, (int) $this->access_level);

        if ($level <= 3) {
            return new HtmlString('');
        }

        $title = e($this->accessLevelLabel($locale));

        return new HtmlString(
            '<span class="room-access-badge" title="'.$title.'" aria-label="'.$title.'">LV-'.$level.'</span> '
        );
    }

    public function accessLevelLabel(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ((int) $this->access_level) {
            0 => $locale === 'th' ? 'ระดับ 0 อ่านได้ทุกคน' : 'Level 0 read access for everyone',
            1 => $locale === 'th' ? 'ระดับ 1 ตอบได้หลังสมัคร 1 เดือน' : 'Level 1 reply after 1 month',
            2 => $locale === 'th' ? 'ระดับ 2 ตอบได้หลังสมัคร 3 เดือน' : 'Level 2 reply after 3 months',
            3 => $locale === 'th' ? 'ระดับ 3 ตั้งหัวข้อได้เต็มรูปแบบ' : 'Level 3 full posting access',
            4 => $locale === 'th' ? 'ระดับ 4 ห้อง VIP' : 'Level 4 VIP access',
            9 => $locale === 'th' ? 'ระดับ 9 เฉพาะผู้ดูแลระบบ' : 'Level 9 admin only',
            default => $locale === 'th'
                ? 'ระดับ '.(int) $this->access_level
                : 'Level '.(int) $this->access_level,
        };
    }

    public function legacyDescriptionHtml(): string
    {
        $description = trim((string) $this->description);

        if ($description === '') {
            return '';
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $description) ?? '';
        $html = strip_tags($html, '<font><br><b><strong><i><em><u>');

        $html = preg_replace_callback('/<font\b([^>]*)>/i', function (array $matches): string {
            if (preg_match('/\bcolor\s*=\s*([\'"]?)([^\'"\s>]+)\1/i', $matches[1], $colorMatches) !== 1) {
                return '<font>';
            }

            $color = $this->sanitizeLegacyColor($colorMatches[2]);

            return $color !== null
                ? '<font color="'.$color.'">'
                : '<font>';
        }, $html) ?? '';

        $html = preg_replace('/<(b|strong|i|em|u)\b[^>]*>/i', '<$1>', $html) ?? '';
        $html = preg_replace('/<br\b[^>]*>/i', '<br>', $html) ?? '';

        return $html;
    }

    private function sanitizeLegacyColor(string $color): ?string
    {
        return LegacyFontTagParser::sanitizeColor($color);
    }
}

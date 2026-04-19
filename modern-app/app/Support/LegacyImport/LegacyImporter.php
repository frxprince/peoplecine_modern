<?php

namespace App\Support\LegacyImport;

use App\Models\Article;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LegacyImporter
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $tableColumns = [
        'memberx' => ['ID', 'FirstName', 'NickName', 'LastName', 'Telephone', 'Email', 'Address', 'PostCode', 'Notice', 'Levelx', 'LastLogin', 'Province', 'Interest1', 'Interest2', 'Interest3', 'Interest4', 'Interest5', 'Interest6', 'Interest7', 'Interest8', 'Interest9', 'Username', 'Password', 'Authorize', 'RegisterDate', 'Visited', 'Other', 'Pic1', 'IDCard', 'AGE', 'SEX', 'CompanyName', 'CompanyTel', 'StatusX', 'AdvicedBy', 'RadioOn', 'HideAddress', 'HidePhone', 'Meeting', 'stars'],
        'grouptopic' => ['ID', 'TopicGroup', 'TopicGroupE', 'GroupDescription', 'PIC1', 'PIC2', 'PIC3', 'PIC4', 'Order_1', 'GroupDescriptionE', 'Level'],
        'grouptopic_owner' => ['id', 'grouptopic', 'login'],
        'topics' => ['ID', 'TopicMessage', 'Detail', 'Poster', 'Email', 'PostDate', 'View0', 'Reply0', 'Replier', 'IP', 'Login', 'LastReply', 'Pic1', 'Pic2', 'Pic3', 'Pic4', 'OrderID', 'GroupID', 'Rate', 'Rank', 'Locked'],
        'reply' => ['ID', 'TopicID', 'TopicMessage', 'ReplyMessage', 'Replier', 'Email', 'ReplyDate', 'IP', 'Login', 'Pic1', 'Pic2', 'Pic3', 'Pic4'],
        'bookmark' => ['ID', 'UserID', 'TopicID', 'DateTime'],
        'article_group' => ['ID', 'GroupName'],
        'article_title' => ['ID', 'Title', 'Owner', 'Datetime', 'OriginalBy', 'Hit', 'Group'],
        'article_body' => ['ID', 'ArticleID', 'Message', 'Pic1', 'Pic2', 'Pic3', 'Pic4'],
        'membermessage' => ['ID', 'MessageFrom', 'ToID', 'MessageTo', 'Message', 'PostDate', 'CheckInDate', 'MessageStatus', 'FromID', 'Broadcast'],
    ];

    public function __construct(
        private readonly LegacyDumpReader $reader,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function import(string $dumpPath, bool $fresh = false, ?OutputStyle $output = null): array
    {
        if ($fresh) {
            $this->freshDatabase();
        }

        $summary = [];
        $summary['users'] = $this->importUsers($dumpPath, $output);
        $summary['rooms'] = $this->importRooms($dumpPath, $output);
        $summary['room_moderators'] = $this->importRoomModerators($dumpPath, $output);
        $summary['topics'] = $this->importTopics($dumpPath, $output);
        $summary['replies'] = $this->importReplies($dumpPath, $output);
        $summary['topic_attachments'] = $this->importPostAttachments($dumpPath, $output);
        $summary['article_categories'] = $this->importArticleCategories($dumpPath, $output);
        $summary['articles'] = $this->importArticles($dumpPath, $output);
        $summary['article_blocks'] = $this->importArticleBlocks($dumpPath, $output);
        $summary['bookmarks'] = $this->importBookmarks($dumpPath, $output);
        $summary['messages'] = $this->importMessages($dumpPath, $output);

        $this->syncTopicPointers();
        $this->syncArticlePreviews();

        return $summary;
    }

    private function freshDatabase(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['audit_logs', 'messages', 'conversation_participants', 'conversations', 'bookmarks', 'attachments', 'posts', 'topics', 'room_moderators', 'rooms', 'article_blocks', 'articles', 'article_categories', 'user_profiles', 'users', 'sessions', 'password_reset_tokens', 'jobs', 'cache', 'cache_locks'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    private function importUsers(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing users...</info>');

        $users = [];
        $profiles = [];
        $inserted = 0;
        $usedEmails = [];
        $usedUsernames = [];

        foreach ($this->reader->iterateRows($dumpPath, 'memberx', $this->tableColumns['memberx']) as $row) {
            $username = $this->uniqueUsername((string) ($row['Username'] ?? ''), (int) $row['ID'], $usedUsernames);
            $users[] = [
                'legacy_memberx_id' => (int) $row['ID'],
                'username' => $username,
                'email' => $this->normalizeEmail($row['Email'], $usedEmails),
                'password' => Hash::make((string) ($row['Password'] ?? '')),
                'password_reset_required' => false,
                'role' => $this->mapRole($row),
                'account_status' => $this->mapAccountStatus($row),
                'legacy_level' => $this->nullableInt($row['Levelx']),
                'legacy_authorize' => $this->nullableString($row['Authorize']),
                'created_at' => $this->normalizeDate($row['RegisterDate']) ?? now(),
                'updated_at' => $this->normalizeDate($row['LastLogin']) ?? now(),
            ];

            $profiles[] = [
                'legacy_memberx_id' => (int) $row['ID'],
                'first_name' => $this->nullableString($row['FirstName']),
                'last_name' => $this->nullableString($row['LastName']),
                'display_name' => $this->displayNameFromRow($row),
                'phone' => $this->nullableString($row['Telephone']),
                'company_name' => $this->nullableString($row['CompanyName']),
                'company_phone' => $this->nullableString($row['CompanyTel']),
                'province' => $this->nullableString($row['Province']),
                'postal_code' => $this->nullableString($row['PostCode']),
                'address' => $this->nullableString($row['Address']),
                'hide_address' => $this->legacyBoolean($row['HideAddress']),
                'biography' => $this->nullableString($row['Other']),
                'interests' => json_encode($this->extractInterests($row), JSON_UNESCAPED_UNICODE),
                'avatar_path' => $this->normalizeAttachmentPath($row['Pic1']),
                'created_at' => $this->normalizeDate($row['RegisterDate']) ?? now(),
                'updated_at' => $this->normalizeDate($row['LastLogin']) ?? now(),
            ];

            if (count($users) >= 250) {
                $inserted += $this->flushUsers($users, $profiles);
                $users = [];
                $profiles = [];
            }
        }

        if ($users !== []) {
            $inserted += $this->flushUsers($users, $profiles);
        }

        return $inserted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<int, array<string, mixed>>  $profiles
     */
    private function flushUsers(array $users, array $profiles): int
    {
        DB::table('users')->insert($users);

        $userIdsByLegacy = DB::table('users')
            ->whereIn('legacy_memberx_id', array_column($users, 'legacy_memberx_id'))
            ->pluck('id', 'legacy_memberx_id')
            ->all();

        $profileRows = [];

        foreach ($profiles as $profile) {
            $userId = $userIdsByLegacy[$profile['legacy_memberx_id']] ?? null;

            if ($userId === null) {
                continue;
            }

            unset($profile['legacy_memberx_id']);
            $profile['user_id'] = $userId;
            $profile['interests'] = $profile['interests'] !== '[]' ? $profile['interests'] : null;
            $profileRows[] = $profile;
        }

        if ($profileRows !== []) {
            DB::table('user_profiles')->insert($profileRows);
        }

        return count($users);
    }

    private function importRooms(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing rooms...</info>');

        $rows = [];
        $usedSlugs = [];

        foreach ($this->reader->iterateRows($dumpPath, 'grouptopic', $this->tableColumns['grouptopic']) as $row) {
            $styledName = LegacyFontTagParser::parse((string) ($row['TopicGroup'] ?? ''));
            $styledNameEn = LegacyFontTagParser::parse((string) ($row['TopicGroupE'] ?? ''));
            $baseName = $styledName['text'] !== '' ? $styledName['text'] : "Room {$row['ID']}";

            $rows[] = [
                'legacy_grouptopic_id' => (int) $row['ID'],
                'slug' => $this->uniqueSlug($baseName, "room-{$row['ID']}", $usedSlugs),
                'name' => $baseName,
                'name_color' => $styledName['color'],
                'name_en' => $styledNameEn['text'] !== '' ? $styledNameEn['text'] : null,
                'description' => $this->nullableString($row['GroupDescription']) ?? $this->nullableString($row['GroupDescriptionE']),
                'access_level' => $this->nullableInt($row['Level']) ?? 0,
                'sort_order' => $this->nullableInt($row['Order_1']) ?? 0,
                'is_archived' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('rooms')->insert($rows);
        }

        return count($rows);
    }

    private function importRoomModerators(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing room moderators...</info>');

        $userIdsByUsername = DB::table('users')->pluck('id', 'username')->all();
        $roomIdsByLegacy = DB::table('rooms')->pluck('id', 'legacy_grouptopic_id')->all();
        $rows = [];
        $seen = [];

        foreach ($this->reader->iterateRows($dumpPath, 'grouptopic_owner', $this->tableColumns['grouptopic_owner']) as $row) {
            $userId = $userIdsByUsername[(string) $row['login']] ?? null;
            $roomId = $roomIdsByLegacy[(int) $row['grouptopic']] ?? null;

            if ($userId === null || $roomId === null) {
                continue;
            }

            $key = $roomId.'-'.$userId;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = ['room_id' => $roomId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()];
        }

        if ($rows !== []) {
            DB::table('room_moderators')->insert($rows);
        }

        return count($rows);
    }

    private function importTopics(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing topics and first posts...</info>');

        $roomIdsByLegacy = DB::table('rooms')->pluck('id', 'legacy_grouptopic_id')->all();
        $userIdsByUsername = DB::table('users')->pluck('id', 'username')->all();
        $fallbackRoomId = $this->fallbackRoomId();
        $topicRows = [];
        $firstPostRows = [];
        $inserted = 0;

        foreach ($this->reader->iterateRows($dumpPath, 'topics', $this->tableColumns['topics']) as $row) {
            $roomId = $roomIdsByLegacy[(int) $row['GroupID']] ?? $fallbackRoomId;

            $legacyId = (int) $row['ID'];
            $createdAt = $this->normalizeDate($row['PostDate']) ?? now();
            $lastPostedAt = $this->normalizeDate($row['LastReply']) ?? $createdAt;

            $topicRows[] = [
                'legacy_topic_id' => $legacyId,
                'room_id' => $roomId,
                'author_id' => $userIdsByUsername[(string) ($row['Login'] ?? '')] ?? null,
                'title' => $this->fallbackTitle($row['TopicMessage'], "Topic {$legacyId}"),
                'visibility_level' => $this->mapVisibility($row['Rate']),
                'is_pinned' => ((string) $row['Rank']) === '1',
                'is_locked' => ((string) $row['Locked']) === '1',
                'view_count' => max(0, (int) ($row['View0'] ?? 0)),
                'reply_count' => max(0, (int) ($row['Reply0'] ?? 0)),
                'legacy_rate' => $this->nullableString($row['Rate']),
                'last_posted_at' => $lastPostedAt,
                'created_at' => $createdAt,
                'updated_at' => $lastPostedAt,
            ];

            $firstPostRows[] = [
                'legacy_topic_id' => $legacyId,
                'author_id' => $userIdsByUsername[(string) ($row['Login'] ?? '')] ?? null,
                'body_html' => $this->sanitizeHtmlBody($row['Detail']),
                'ip_address' => $this->nullableString($row['IP']),
                'created_at' => $createdAt,
                'updated_at' => $lastPostedAt,
            ];

            if (count($topicRows) >= 250) {
                $inserted += $this->flushTopics($topicRows, $firstPostRows);
                $topicRows = [];
                $firstPostRows = [];
            }
        }

        if ($topicRows !== []) {
            $inserted += $this->flushTopics($topicRows, $firstPostRows);
        }

        return $inserted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $topics
     * @param  array<int, array<string, mixed>>  $firstPosts
     */
    private function flushTopics(array $topics, array $firstPosts): int
    {
        DB::table('topics')->insert($topics);

        $topicIdsByLegacy = DB::table('topics')
            ->whereIn('legacy_topic_id', array_column($topics, 'legacy_topic_id'))
            ->pluck('id', 'legacy_topic_id')
            ->all();

        $rows = [];

        foreach ($firstPosts as $firstPost) {
            $topicId = $topicIdsByLegacy[$firstPost['legacy_topic_id']] ?? null;

            if ($topicId === null) {
                continue;
            }

            $rows[] = [
                'topic_id' => $topicId,
                'author_id' => $firstPost['author_id'],
                'legacy_source_table' => 'topics',
                'legacy_source_id' => $firstPost['legacy_topic_id'],
                'position_in_topic' => 1,
                'body_html' => $firstPost['body_html'],
                'ip_address' => $firstPost['ip_address'],
                'created_at' => $firstPost['created_at'],
                'updated_at' => $firstPost['updated_at'],
            ];
        }

        if ($rows !== []) {
            DB::table('posts')->insert($rows);
        }

        return count($topics);
    }

    private function fallbackRoomId(): int
    {
        $existingId = DB::table('rooms')->where('slug', 'legacy-orphans')->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) DB::table('rooms')->insertGetId([
            'legacy_grouptopic_id' => null,
            'slug' => 'legacy-orphans',
            'name' => 'Legacy Orphans',
            'name_en' => 'Legacy Orphans',
            'description' => 'Topics whose original room reference no longer exists in the legacy dump.',
            'access_level' => 0,
            'sort_order' => 9999,
            'is_archived' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function importReplies(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing replies...</info>');

        $topicIdsByLegacy = DB::table('topics')->pluck('id', 'legacy_topic_id')->all();
        $userIdsByUsername = DB::table('users')->pluck('id', 'username')->all();
        $positions = DB::table('posts')->select('topic_id', DB::raw('MAX(position_in_topic) as max_position'))->groupBy('topic_id')->pluck('max_position', 'topic_id')->all();
        $rows = [];
        $inserted = 0;

        foreach ($this->reader->iterateRows($dumpPath, 'reply', $this->tableColumns['reply']) as $row) {
            $topicId = $topicIdsByLegacy[(int) $row['TopicID']] ?? null;

            if ($topicId === null) {
                continue;
            }

            $positions[$topicId] = ($positions[$topicId] ?? 1) + 1;
            $createdAt = $this->normalizeDate($row['ReplyDate']) ?? now();

            $rows[] = [
                'topic_id' => $topicId,
                'author_id' => $userIdsByUsername[(string) ($row['Login'] ?? '')] ?? null,
                'legacy_source_table' => 'reply',
                'legacy_source_id' => (int) $row['ID'],
                'position_in_topic' => $positions[$topicId],
                'body_html' => $this->sanitizeHtmlBody($row['ReplyMessage']),
                'ip_address' => $this->nullableString($row['IP']),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($rows) >= 500) {
                DB::table('posts')->insert($rows);
                $inserted += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('posts')->insert($rows);
            $inserted += count($rows);
        }

        return $inserted;
    }

    private function importPostAttachments(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing topic and reply attachments...</info>');

        $postIdsByLegacyTopic = DB::table('posts')->where('legacy_source_table', 'topics')->pluck('id', 'legacy_source_id')->all();
        $postIdsByLegacyReply = DB::table('posts')->where('legacy_source_table', 'reply')->pluck('id', 'legacy_source_id')->all();
        $rows = [];
        $inserted = 0;

        foreach (['topics', 'reply'] as $table) {
            $mapping = $table === 'topics' ? $postIdsByLegacyTopic : $postIdsByLegacyReply;

            foreach ($this->reader->iterateRows($dumpPath, $table, $this->tableColumns[$table]) as $row) {
                $postId = $mapping[(int) $row['ID']] ?? null;

                if ($postId === null) {
                    continue;
                }

                foreach (['Pic1', 'Pic2', 'Pic3', 'Pic4'] as $index => $column) {
                    $path = $this->normalizeAttachmentPath($row[$column] ?? null);

                    if ($path === null) {
                        continue;
                    }

                    $rows[] = [
                        'attachable_type' => 'post',
                        'attachable_id' => $postId,
                        'slot_no' => $index + 1,
                        'legacy_path' => $path,
                        'storage_disk' => 'legacy',
                        'stored_path' => null,
                        'original_filename' => basename($path),
                        'mime_type' => null,
                        'size_bytes' => null,
                        'width' => null,
                        'height' => null,
                        'checksum_sha256' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($rows) >= 500) {
                    DB::table('attachments')->insert($rows);
                    $inserted += count($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('attachments')->insert($rows);
            $inserted += count($rows);
        }

        return $inserted;
    }

    private function importArticleCategories(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing article categories...</info>');

        $rows = [];
        $usedSlugs = [];

        foreach ($this->reader->iterateRows($dumpPath, 'article_group', $this->tableColumns['article_group']) as $row) {
            $rows[] = [
                'legacy_article_group_id' => (int) $row['ID'],
                'slug' => $this->uniqueSlug((string) ($row['GroupName'] ?? ''), "article-group-{$row['ID']}", $usedSlugs),
                'name' => $this->fallbackTitle($row['GroupName'], "Article group {$row['ID']}"),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('article_categories')->insert($rows);
        }

        return count($rows);
    }

    private function importArticles(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing articles...</info>');

        $categoryIdsByLegacy = DB::table('article_categories')->pluck('id', 'legacy_article_group_id')->all();
        $userIdsByUsername = DB::table('users')->pluck('id', 'username')->all();
        $rows = [];
        $usedSlugs = [];

        foreach ($this->reader->iterateRows($dumpPath, 'article_title', $this->tableColumns['article_title']) as $row) {
            $rows[] = [
                'legacy_article_title_id' => (int) $row['ID'],
                'category_id' => $categoryIdsByLegacy[(int) $row['Group']] ?? null,
                'author_id' => $userIdsByUsername[(string) ($row['Owner'] ?? '')] ?? null,
                'title' => $this->fallbackTitle($row['Title'], "Article {$row['ID']}"),
                'slug' => $this->uniqueSlug((string) ($row['Title'] ?? ''), "article-{$row['ID']}", $usedSlugs),
                'source_name' => $this->nullableString($row['OriginalBy']),
                'body_preview' => null,
                'published_at' => $this->normalizeDate($row['Datetime']),
                'view_count' => max(0, (int) ($row['Hit'] ?? 0)),
                'created_at' => $this->normalizeDate($row['Datetime']) ?? now(),
                'updated_at' => $this->normalizeDate($row['Datetime']) ?? now(),
            ];
        }

        if ($rows !== []) {
            DB::table('articles')->insert($rows);
        }

        return count($rows);
    }

    private function importArticleBlocks(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing article blocks and attachments...</info>');

        $articleIdsByLegacy = DB::table('articles')->pluck('id', 'legacy_article_title_id')->all();
        $positions = [];
        $blocks = [];
        $meta = [];
        $inserted = 0;

        foreach ($this->reader->iterateRows($dumpPath, 'article_body', $this->tableColumns['article_body']) as $row) {
            $articleId = $articleIdsByLegacy[(int) $row['ArticleID']] ?? null;

            if ($articleId === null) {
                continue;
            }

            $positions[$articleId] = ($positions[$articleId] ?? 0) + 1;

            $blocks[] = [
                'legacy_article_body_id' => (int) $row['ID'],
                'article_id' => $articleId,
                'position_in_article' => $positions[$articleId],
                'body_html' => $this->sanitizeHtmlBody($row['Message']),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $meta[] = $row;

            if (count($blocks) >= 250) {
                $inserted += $this->flushArticleBlocks($blocks, $meta);
                $blocks = [];
                $meta = [];
            }
        }

        if ($blocks !== []) {
            $inserted += $this->flushArticleBlocks($blocks, $meta);
        }

        return $inserted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<int, array<string, mixed>>  $meta
     */
    private function flushArticleBlocks(array $blocks, array $meta): int
    {
        DB::table('article_blocks')->insert($blocks);

        $blockIdsByLegacy = DB::table('article_blocks')->whereIn('legacy_article_body_id', array_column($blocks, 'legacy_article_body_id'))->pluck('id', 'legacy_article_body_id')->all();
        $attachments = [];

        foreach ($meta as $row) {
            $blockId = $blockIdsByLegacy[(int) $row['ID']] ?? null;

            if ($blockId === null) {
                continue;
            }

            foreach (['Pic1', 'Pic2', 'Pic3', 'Pic4'] as $index => $column) {
                $path = $this->normalizeAttachmentPath($row[$column] ?? null);

                if ($path === null) {
                    continue;
                }

                $attachments[] = [
                    'attachable_type' => 'article_block',
                    'attachable_id' => $blockId,
                    'slot_no' => $index + 1,
                    'legacy_path' => $path,
                    'storage_disk' => 'legacy',
                    'stored_path' => null,
                    'original_filename' => basename($path),
                    'mime_type' => null,
                    'size_bytes' => null,
                    'width' => null,
                    'height' => null,
                    'checksum_sha256' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($attachments !== []) {
            DB::table('attachments')->insert($attachments);
        }

        return count($blocks);
    }

    private function importBookmarks(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing bookmarks...</info>');

        $userIdsByLegacy = DB::table('users')->pluck('id', 'legacy_memberx_id')->all();
        $topicIdsByLegacy = DB::table('topics')->pluck('id', 'legacy_topic_id')->all();
        $rows = [];
        $inserted = 0;

        foreach ($this->reader->iterateRows($dumpPath, 'bookmark', $this->tableColumns['bookmark']) as $row) {
            $userId = $userIdsByLegacy[(int) $row['UserID']] ?? null;
            $topicId = $topicIdsByLegacy[(int) $row['TopicID']] ?? null;

            if ($userId === null || $topicId === null) {
                continue;
            }

            $rows[] = [
                'legacy_bookmark_id' => (int) $row['ID'],
                'user_id' => $userId,
                'topic_id' => $topicId,
                'created_at' => $this->normalizeDate($row['DateTime']) ?? now(),
                'updated_at' => $this->normalizeDate($row['DateTime']) ?? now(),
            ];

            if (count($rows) >= 500) {
                DB::table('bookmarks')->insertOrIgnore($rows);
                $inserted += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('bookmarks')->insertOrIgnore($rows);
            $inserted += count($rows);
        }

        return $inserted;
    }

    private function importMessages(string $dumpPath, ?OutputStyle $output = null): int
    {
        $output?->writeln('<info>Importing private messages...</info>');

        $userIdsByLegacy = DB::table('users')->pluck('id', 'legacy_memberx_id')->all();
        $userIdsByUsername = DB::table('users')->pluck('id', 'username')->all();
        $conversations = [];
        $messages = [];
        $inserted = 0;

        foreach ($this->reader->iterateRows($dumpPath, 'membermessage', $this->tableColumns['membermessage']) as $row) {
            $legacyId = (int) $row['ID'];
            $createdAt = $this->normalizeDate($row['PostDate']) ?? now();
            $conversations[] = [
                'legacy_source' => "membermessage:{$legacyId}",
                'subject' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            $messages[] = [
                'legacy_membermessage_id' => $legacyId,
                'sender_id' => $userIdsByLegacy[(int) ($row['FromID'] ?? 0)] ?? ($userIdsByUsername[(string) ($row['MessageFrom'] ?? '')] ?? null),
                'recipient_id' => $userIdsByLegacy[(int) ($row['ToID'] ?? 0)] ?? ($userIdsByUsername[(string) ($row['MessageTo'] ?? '')] ?? null),
                'body_html' => $this->sanitizeHtmlBody($row['Message']),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($conversations) >= 200) {
                $inserted += $this->flushMessages($conversations, $messages);
                $conversations = [];
                $messages = [];
            }
        }

        if ($conversations !== []) {
            $inserted += $this->flushMessages($conversations, $messages);
        }

        return $inserted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $conversations
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function flushMessages(array $conversations, array $messages): int
    {
        DB::table('conversations')->insert($conversations);

        $conversationIds = DB::table('conversations')->whereIn('legacy_source', array_column($conversations, 'legacy_source'))->pluck('id', 'legacy_source')->all();
        $messageRows = [];
        $participantRows = [];

        foreach ($messages as $message) {
            $conversationId = $conversationIds['membermessage:'.$message['legacy_membermessage_id']] ?? null;

            if ($conversationId === null) {
                continue;
            }

            $recipientId = $message['recipient_id'];
            unset($message['recipient_id']);

            $message['conversation_id'] = $conversationId;
            $messageRows[] = $message;

            foreach (array_filter([$message['sender_id'], $recipientId]) as $userId) {
                $participantRows[$conversationId.'-'.$userId] = [
                    'conversation_id' => $conversationId,
                    'user_id' => $userId,
                    'joined_at' => $message['created_at'],
                    'last_read_at' => null,
                    'created_at' => $message['created_at'],
                    'updated_at' => $message['updated_at'],
                ];
            }
        }

        if ($messageRows !== []) {
            DB::table('messages')->insert($messageRows);
        }

        if ($participantRows !== []) {
            DB::table('conversation_participants')->insert(array_values($participantRows));
        }

        return count($messageRows);
    }

    private function syncTopicPointers(): void
    {
        DB::statement('UPDATE topics SET first_post_id = (SELECT MIN(posts.id) FROM posts WHERE posts.topic_id = topics.id), last_post_id = (SELECT MAX(posts.id) FROM posts WHERE posts.topic_id = topics.id), last_posted_at = COALESCE((SELECT MAX(posts.created_at) FROM posts WHERE posts.topic_id = topics.id), last_posted_at)');
    }

    private function syncArticlePreviews(): void
    {
        $articles = Article::query()->with('blocks')->get();

        foreach ($articles as $article) {
            $preview = $article->blocks->pluck('body_html')->map(fn (?string $html) => trim(strip_tags((string) $html)))->first(fn (string $text) => $text !== '');
            $article->forceFill(['body_preview' => $preview ? Str::limit($preview, 240) : null])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, int>
     */
    private function extractInterests(array $row): array
    {
        $interests = [];

        foreach (range(1, 9) as $number) {
            if (($row["Interest{$number}"] ?? null) === 'ON') {
                $interests[] = $number;
            }
        }

        return $interests;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function displayNameFromRow(array $row): string
    {
        $nickname = trim((string) ($row['NickName'] ?? ''));

        if ($nickname !== '') {
            return $nickname;
        }

        $name = trim(implode(' ', array_filter([$this->nullableString($row['FirstName']), $this->nullableString($row['LastName'])])));

        return $name !== '' ? $name : ((string) ($row['Username'] ?? 'member'));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function mapRole(array $row): string
    {
        if (strtolower((string) ($row['Authorize'] ?? '')) === 'admin') {
            return 'admin';
        }

        return 'user';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function mapAccountStatus(array $row): string
    {
        $status = strtolower((string) ($row['StatusX'] ?? ''));
        $authorize = strtolower((string) ($row['Authorize'] ?? ''));

        return $status === 'ban' || $authorize === 'ban'
            ? 'banned'
            : 'active';
    }

    private function mapVisibility(mixed $rate): int
    {
        return match ((string) $rate) {
            'A' => 1,
            'X' => 2,
            default => 0,
        };
    }

    /**
     * @param  array<string, bool>  $usedEmails
     */
    private function normalizeEmail(mixed $value, array &$usedEmails): ?string
    {
        $email = $this->nullableString($value);

        if ($email === null) {
            return null;
        }

        $email = Str::lower($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || isset($usedEmails[$email])) {
            return null;
        }

        $usedEmails[$email] = true;

        return $email;
    }

    /**
     * @param  array<string, bool>  $usedUsernames
     */
    private function uniqueUsername(string $username, int $legacyId, array &$usedUsernames): string
    {
        $base = trim($username) !== '' ? trim($username) : "member-{$legacyId}";
        $candidate = $base;
        $suffix = 2;

        while (isset($usedUsernames[$candidate])) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        $usedUsernames[$candidate] = true;

        return $candidate;
    }

    /**
     * @param  array<string, bool>  $usedSlugs
     */
    private function uniqueSlug(string $source, string $fallback, array &$usedSlugs): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : Str::slug($fallback);
        $candidate = $base;
        $suffix = 2;

        while (isset($usedSlugs[$candidate])) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        $usedSlugs[$candidate] = true;

        return $candidate;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $string = $this->nullableString($value);

        if ($string === null || str_starts_with($string, '0000-00-00')) {
            return null;
        }

        return $string;
    }

    private function normalizeAttachmentPath(mixed $value): ?string
    {
        $path = $this->nullableString($value);

        if ($path === null || $path === '-') {
            return null;
        }

        return ltrim($path, './');
    }

    private function sanitizeHtmlBody(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function legacyBoolean(mixed $value): bool
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['1', 'Y', 'YES', 'TRUE', 'ON'], true);
    }

    private function fallbackTitle(mixed $value, string $fallback): string
    {
        return $this->nullableString($value) ?? $fallback;
    }
}

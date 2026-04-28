@extends('layouts.app', ['title' => app()->getLocale() === 'th' ? 'สถิติระบบ' : 'System Statistics'])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')
    @php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $t('โปรแกรมเมอร์', 'Programmer') }}</p>
        <h1>{{ $t('แดชบอร์ดสถิติ', 'Statistics Dashboard') }}</h1>
        <p class="lede">
            {{ $t(
                'สรุปภาพรวมผู้เข้าชม การใช้งานของสมาชิก และกิจกรรมการโพสต์จากข้อมูลที่ระบบเก็บไว้ในปัจจุบัน',
                'A consolidated view of visitor activity, member usage, and posting statistics from the data currently tracked by the system.'
            ) }}
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('debug.index') }}">{{ $t('กลับไปหน้าดีบัก', 'Back to Debug') }}</a>
        </div>
    </section>

    <section class="stats-grid stats-grid--dashboard">
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('สมาชิกทั้งหมด', 'Total Members') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['members']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('คลิกสะสมของสมาชิก', 'Total Member Clicks') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['member_clicks']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('ผู้เข้าชมที่เก็บไว้', 'Stored Visitors') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['stored_visitors']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('ผู้เยี่ยมชมแบบไม่ล็อกอิน', 'Guest Visitors') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['guest_visitors']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('หัวข้อทั้งหมด', 'Total Topics') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['topics']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('โพสต์ทั้งหมด', 'Total Posts') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['posts']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('ยอดอ่านรวมของหัวข้อ', 'Total Topic Views') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['topic_views']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('จำนวนตอบรวม', 'Total Replies') }}</span>
            <strong class="stat-card__value">{{ number_format($overview['topic_replies']) }}</strong>
        </article>
    </section>

    <section class="section-grid">
        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('ผู้เข้าชมล่าสุด 20 ราย', 'Latest 20 Visitors') }}</h2>
                <p>{{ $t('รวมสมาชิกและผู้เยี่ยมชมที่ไม่ได้ล็อกอิน โดยเรียงตามเวลาที่เข้าชมล่าสุด', 'Includes members and guests, ordered by most recent visit time.') }}</p>
            </div>

            <div class="debug-table-wrap">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>{{ $t('ผู้เข้าชม', 'Visitor') }}</th>
                            <th>{{ $t('ประเภท', 'Type') }}</th>
                            <th>{{ $t('IP', 'IP') }}</th>
                            <th>{{ $t('ล่าสุด', 'Last Seen') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentVisitors as $visitor)
                            <tr>
                                <td>{{ $visitor['label'] }}</td>
                                <td>{{ $visitor['is_guest'] ? $t('ผู้เยี่ยมชม', 'Guest') : $t('สมาชิก', 'Member') }}</td>
                                <td><code>{{ $visitor['ip_address'] }}</code></td>
                                <td>{{ $visitor['last_seen'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ $t('ยังไม่มีข้อมูลผู้เข้าชม', 'No visitor data available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('สมาชิกที่คลิกมากที่สุด', 'Top Member Visitors') }}</h2>
                <p>{{ $t('นับจากตัวนับคลิกสะสมของสมาชิกแต่ละบัญชี', 'Ranked by each member account visit counter.') }}</p>
            </div>

            <div class="debug-table-wrap">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>{{ $t('สมาชิก', 'Member') }}</th>
                            <th>{{ $t('ชื่อผู้ใช้', 'Username') }}</th>
                            <th>{{ $t('ระดับ', 'Level') }}</th>
                            <th>{{ $t('คลิก', 'Clicks') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topVisitors as $member)
                            <tr>
                                <td>{{ $member->displayName() }}</td>
                                <td>{{ $member->username }}</td>
                                <td>{{ $member->memberLevel() }}</td>
                                <td>{{ number_format((int) $member->visit_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ $t('ยังไม่มีสมาชิกที่มีข้อมูลคลิก', 'No member click data available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('หัวข้อที่ถูกอ่านมากที่สุด', 'Top Viewed Topics') }}</h2>
                <p>{{ $t('ช่วยดูว่าหัวข้อใดได้รับความสนใจสูงที่สุด', 'Highlights the discussions drawing the most attention.') }}</p>
            </div>

            <div class="debug-table-wrap">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>{{ $t('หัวข้อ', 'Topic') }}</th>
                            <th>{{ $t('ห้อง', 'Room') }}</th>
                            <th>{{ $t('อ่าน', 'Views') }}</th>
                            <th>{{ $t('ตอบ', 'Replies') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topViewedTopics as $topic)
                            <tr>
                                <td><a href="{{ route('topics.show', $topic) }}">{{ $topic->title }}</a></td>
                                <td>{{ $topic->room?->localizedName() ?? $t('คลังข้อมูล', 'Archive') }}</td>
                                <td>{{ number_format((int) $topic->view_count) }}</td>
                                <td>{{ number_format((int) $topic->reply_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ $t('ยังไม่มีข้อมูลหัวข้อ', 'No topic data available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('หัวข้อที่มีการตอบมากที่สุด', 'Most Replied Topics') }}</h2>
                <p>{{ $t('ใช้ตรวจสอบว่าหัวข้อใดมีการสนทนาคึกคักที่สุด', 'Shows which topics are generating the most discussion.') }}</p>
            </div>

            <div class="debug-table-wrap">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>{{ $t('หัวข้อ', 'Topic') }}</th>
                            <th>{{ $t('ห้อง', 'Room') }}</th>
                            <th>{{ $t('ตอบ', 'Replies') }}</th>
                            <th>{{ $t('อ่าน', 'Views') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topRepliedTopics as $topic)
                            <tr>
                                <td><a href="{{ route('topics.show', $topic) }}">{{ $topic->title }}</a></td>
                                <td>{{ $topic->room?->localizedName() ?? $t('คลังข้อมูล', 'Archive') }}</td>
                                <td>{{ number_format((int) $topic->reply_count) }}</td>
                                <td>{{ number_format((int) $topic->view_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ $t('ยังไม่มีข้อมูลหัวข้อ', 'No topic data available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('ห้องที่คึกคักที่สุด', 'Busiest Rooms') }}</h2>
                <p>{{ $t('สรุปจำนวนหัวข้อ ยอดอ่าน และจำนวนตอบรวมของแต่ละห้อง', 'Summarizes topic counts, views, and replies by room.') }}</p>
            </div>

            <div class="debug-table-wrap">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>{{ $t('ห้อง', 'Room') }}</th>
                            <th>{{ $t('หัวข้อ', 'Topics') }}</th>
                            <th>{{ $t('อ่านรวม', 'Views') }}</th>
                            <th>{{ $t('ตอบรวม', 'Replies') }}</th>
                            <th>{{ $t('กิจกรรมล่าสุด', 'Latest Activity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roomStats as $room)
                            <tr>
                                <td><a href="{{ route('rooms.show', $room) }}">{!! $room->coloredLocalizedNameHtml() !!}</a></td>
                                <td>{{ number_format((int) $room->topics_count) }}</td>
                                <td>{{ number_format((int) ($room->total_views ?? 0)) }}</td>
                                <td>{{ number_format((int) ($room->total_replies ?? 0)) }}</td>
                                <td>{{ optional($room->latestTopic?->last_posted_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ $t('ยังไม่มีข้อมูลห้อง', 'No room data available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('โพสต์ล่าสุดพร้อม IP', 'Recent Posts with IP') }}</h2>
                <p>{{ $t('ดูโพสต์ล่าสุดพร้อมลำดับในหัวข้อ ชื่อผู้โพสต์ และ IP ที่บันทึกไว้', 'Review the latest posts with topic position, author, and stored IP address.') }}</p>
            </div>

            <div class="debug-table-wrap">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>{{ $t('เวลา', 'Time') }}</th>
                            <th>{{ $t('ผู้โพสต์', 'Author') }}</th>
                            <th>{{ $t('หัวข้อ', 'Topic') }}</th>
                            <th>{{ $t('ห้อง', 'Room') }}</th>
                            <th>{{ $t('ลำดับโพสต์', 'Post #') }}</th>
                            <th>{{ $t('IP', 'IP') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentPosts as $post)
                            <tr>
                                <td>{{ $post['created_at'] }}</td>
                                <td>{{ $post['author'] }}</td>
                                <td>{{ $post['topic_title'] }}</td>
                                <td>{{ $post['room_name'] }}</td>
                                <td>#{{ $post['position_in_topic'] }}</td>
                                <td><code>{{ $post['ip_address'] }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">{{ $t('ยังไม่มีข้อมูลโพสต์', 'No post data available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.app', ['title' => __('Room Management')])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Admin') }}</p>
        <h1>{{ __('Room Management') }}</h1>
        <p class="lede">
            {{ __('Configure forum room access permissions here. The access level is the minimum member level required to enter a room.') }}
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('admin.users.index') }}">{{ __('User Management') }}</a>
        </div>
    </section>

    <section class="panel panel--tight">
        <div class="panel__header">
            <h2>{{ __('Create New Room') }}</h2>
            <p>{{ __('Add a new forum room and define its access permission from the start.') }}</p>
        </div>

        <form class="form-stack" method="POST" action="{{ route('admin.rooms.store') }}">
            @csrf

            <div class="admin-room-create-grid">
                <div class="form-field">
                    <label for="room_name">{{ __('Room Name') }}</label>
                    <input id="room_name" name="name" type="text" value="{{ old('name') }}" maxlength="255" required>
                </div>

                <div class="form-field">
                    <label for="room_slug">{{ __('Slug') }}</label>
                    <input id="room_slug" name="slug" type="text" value="{{ old('slug') }}" maxlength="255" placeholder="{{ __('Optional auto-generated if empty') }}">
                </div>

                <div class="form-field">
                    <label for="room_name_en">{{ __('English Name') }}</label>
                    <input id="room_name_en" name="name_en" type="text" value="{{ old('name_en') }}" maxlength="255">
                </div>

                <div class="form-field">
                    <label for="room_name_color">{{ __('Name Color') }}</label>
                    <input id="room_name_color" name="name_color" type="text" value="{{ old('name_color') }}" maxlength="7" placeholder="#FF00FF">
                </div>

                <div class="form-field">
                    <label for="room_access_level">{{ __('Access Level') }}</label>
                    <select id="room_access_level" name="access_level">
                        @foreach ([0, 1, 2, 3, 4, 9, 10] as $level)
                            <option value="{{ $level }}" @selected((int) old('access_level', 0) === $level)>
                                {{ match($level) {
                                    0 => __('0 Read All'),
                                    1 => __('1 Reply 1M'),
                                    2 => __('2 Reply 3M'),
                                    3 => __('3 Full Post'),
                                    4 => __('4 VIP'),
                                    9 => __('9 Admin Only'),
                                    10 => __('10 Programmer Only'),
                                } }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label for="room_sort_order">{{ __('Sort Order') }}</label>
                    <input id="room_sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', 0) }}" required>
                </div>
            </div>

            <div class="form-field">
                <label for="room_description">{{ __('Description') }}</label>
                <textarea id="room_description" name="description" rows="4">{{ old('description') }}</textarea>
            </div>

            <label class="checkbox-field" for="room_is_archived">
                <input id="room_is_archived" name="is_archived" type="checkbox" value="1" @checked(old('is_archived'))>
                <span>{{ __('Create this room as archived') }}</span>
            </label>

            @if ($errors->any())
                @foreach ($errors->all() as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            @endif

            <div class="inline-actions">
                <button class="button" type="submit">{{ __('Create Room') }}</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Forum Rooms') }}</h2>
            <p>{{ __(':count imported rooms.', ['count' => number_format($rooms->count())]) }}</p>
        </div>

        <div class="admin-user-table-wrap">
            <table class="admin-user-table">
                <thead>
                    <tr>
                        <th width="30%">{{ __('Room') }}</th>
                        <th width="18%">{{ __('Slug') }}</th>
                        <th width="10%">{{ __('Topics') }}</th>
                        <th width="14%">{{ __('Access') }}</th>
                        <th width="12%">{{ __('Sort') }}</th>
                        <th width="8%">{{ __('Archive') }}</th>
                        <th width="8%">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        @php($formId = 'manage-room-'.$room->id)
                        <tr>
                            <td>
                                <strong>{!! $room->coloredNameHtml() !!}</strong>
                                @if ($room->name_en)
                                    <div class="forum-last-meta">{{ $room->name_en }}</div>
                                @endif
                            </td>
                            <td><code>{{ $room->slug }}</code></td>
                            <td class="forum-table__number">{{ number_format($room->topics_count) }}</td>
                            <td class="admin-user-table__cell-control">
                                <select name="access_level" form="{{ $formId }}">
                                    @foreach ([0, 1, 2, 3, 4, 9, 10] as $level)
                                        <option value="{{ $level }}" @selected((int) $room->access_level === $level)>
                                            {{ match($level) {
                                                0 => __('0 Read All'),
                                                1 => __('1 Reply 1M'),
                                                2 => __('2 Reply 3M'),
                                                3 => __('3 Full Post'),
                                                4 => __('4 VIP'),
                                                9 => __('9 Admin Only'),
                                                10 => __('10 Programmer Only'),
                                            } }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="admin-user-table__cell-control">
                                <input
                                    class="admin-room-table__sort-input"
                                    name="sort_order"
                                    type="number"
                                    min="0"
                                    max="9999"
                                    value="{{ $room->sort_order }}"
                                    form="{{ $formId }}"
                                >
                            </td>
                            <td class="admin-user-table__select">
                                <input
                                    class="admin-user-table__checkbox"
                                    name="is_archived"
                                    type="checkbox"
                                    value="1"
                                    @checked($room->is_archived)
                                    form="{{ $formId }}"
                                    aria-label="{{ __('Archive :name', ['name' => $room->name]) }}"
                                >
                            </td>
                            <td class="admin-user-table__action">
                                <form
                                    id="{{ $formId }}"
                                    class="admin-user-form"
                                    method="POST"
                                    action="{{ route('admin.rooms.update', $room) }}"
                                >
                                    @csrf
                                    @method('PUT')
                                    <button class="button button--small" type="submit">{{ __('Save') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </section>
@endsection

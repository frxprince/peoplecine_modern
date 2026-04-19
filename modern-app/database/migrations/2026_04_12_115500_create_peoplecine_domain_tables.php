<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('address')->nullable();
            $table->longText('biography')->nullable();
            $table->json('interests')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_grouptopic_id')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->longText('description')->nullable();
            $table->integer('access_level')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('room_moderators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['room_id', 'user_id']);
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_topic_id')->nullable()->unique();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 500);
            $table->integer('visibility_level')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('reply_count')->default(0);
            $table->string('legacy_rate', 10)->nullable();
            $table->unsignedBigInteger('first_post_id')->nullable();
            $table->unsignedBigInteger('last_post_id')->nullable();
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['room_id', 'last_posted_at']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('legacy_source_table', 30);
            $table->unsignedInteger('legacy_source_id');
            $table->unsignedInteger('position_in_topic');
            $table->longText('body_html');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['legacy_source_table', 'legacy_source_id']);
            $table->unique(['topic_id', 'position_in_topic']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 50);
            $table->unsignedBigInteger('attachable_id');
            $table->unsignedTinyInteger('slot_no')->default(1);
            $table->string('legacy_path')->nullable();
            $table->string('storage_disk', 50)->default('public');
            $table->string('stored_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_bookmark_id')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'topic_id']);
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_article_group_id')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_article_title_id')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('source_name')->nullable();
            $table->text('body_preview')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('article_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_article_body_id')->nullable()->unique();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position_in_article');
            $table->longText('body_html');
            $table->timestamps();
            $table->unique(['article_id', 'position_in_article']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_source')->nullable()->unique();
            $table->string('subject')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_membermessage_id')->nullable()->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('body_html');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('article_blocks');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('room_moderators');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('user_profiles');
    }
};

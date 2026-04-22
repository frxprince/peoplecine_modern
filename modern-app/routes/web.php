<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\BannerManagementController;
use App\Http\Controllers\Admin\RoomManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectMessagePreferenceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyArticleMediaController;
use App\Http\Controllers\LegacyArticlePdfController;
use App\Http\Controllers\LegacyMediaController;
use App\Http\Controllers\PrivateMessageController;
use App\Http\Controllers\ComposerUploadController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectorManualController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'landing'])->name('landing');
Route::get('/forum', [HomeController::class, 'forum'])->name('home');
Route::get('/lang/{locale}', LocaleController::class)->name('locale.switch');
Route::get('/calculator/throw', [CalculatorController::class, 'throwSelector'])->name('calculator.throw');
Route::get('/calculator/throw/{screen}', [CalculatorController::class, 'throwCalculator'])
    ->where('screen', 'scope|flat')
    ->name('calculator.throw.screen');
Route::get('/calculator/lenssim', [CalculatorController::class, 'lensSimulation'])->name('calculator.lenssim');
Route::get('/calculator/screendesign', [CalculatorController::class, 'screenDesign'])->name('calculator.screendesign');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/projector-manual', [ProjectorManualController::class, 'index'])->name('projector-manual.index');
Route::get('/rooms/{room:slug}', [RoomController::class, 'show'])->name('rooms.show');
Route::get('/topics/{topic}', [TopicController::class, 'show'])->name('topics.show');
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/pdf/{filename}', LegacyArticlePdfController::class)->where('filename', '[^/\\\\]+')->name('legacy-article-pdf.show');
Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/legacy-article-media', LegacyArticleMediaController::class)->name('legacy-article-media.show');
Route::get('/legacy-media/{attachment}', LegacyMediaController::class)->name('legacy-media.show');
Route::get('/avatars/{user}', AvatarController::class)->name('avatars.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/composer/uploads', [ComposerUploadController::class, 'store'])->name('composer.uploads.store');
    Route::delete('/composer/uploads/{stagedUpload}', [ComposerUploadController::class, 'destroy'])->name('composer.uploads.destroy');
    Route::post('/rooms/{room:slug}/topics', [RoomController::class, 'storeTopic'])->name('rooms.topics.store');
    Route::post('/topics/{topic}/replies', [TopicController::class, 'storeReply'])->name('topics.replies.store');
    Route::post('/topics/{topic}/bookmark', [TopicController::class, 'storeBookmark'])->name('topics.bookmarks.store');
    Route::delete('/topics/{topic}/bookmark', [TopicController::class, 'destroyBookmark'])->name('topics.bookmarks.destroy');
    Route::post('/topics/{topic}/pin', [TopicController::class, 'pin'])->name('topics.pin');
    Route::delete('/topics/{topic}/pin', [TopicController::class, 'unpin'])->name('topics.unpin');
    Route::put('/topics/{topic}/posts/{post}', [TopicController::class, 'updatePost'])->name('topics.posts.update');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroyTopic'])->name('topics.destroy');
    Route::delete('/topics/{topic}/posts/{post}', [TopicController::class, 'destroyPost'])->name('topics.posts.destroy');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/messages', [PrivateMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [PrivateMessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [PrivateMessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{conversation}', [PrivateMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/reply', [PrivateMessageController::class, 'reply'])->name('messages.reply');
    Route::post('/messages/{conversation}/archive', [PrivateMessageController::class, 'archive'])->name('messages.archive');
    Route::delete('/messages/{conversation}/archive', [PrivateMessageController::class, 'unarchive'])->name('messages.archive.destroy');
    Route::delete('/messages', [PrivateMessageController::class, 'destroyMany'])->name('messages.destroy-many');
    Route::delete('/messages/{conversation}', [PrivateMessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/members/{user}/block', [DirectMessagePreferenceController::class, 'block'])->name('members.block');
    Route::delete('/members/{user}/block', [DirectMessagePreferenceController::class, 'unblock'])->name('members.unblock');
    Route::post('/members/{user}/mute', [DirectMessagePreferenceController::class, 'mute'])->name('members.mute');
    Route::delete('/members/{user}/mute', [DirectMessagePreferenceController::class, 'unmute'])->name('members.unmute');
    Route::get('/members/{user}', [ProfileController::class, 'show'])->name('members.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'password.reset.completed'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'password.reset.completed', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/password', [UserManagementController::class, 'updatePassword'])->name('users.password.update');
    Route::post('/users/mail-test', [UserManagementController::class, 'sendTestMail'])->name('users.mail-test');
    Route::delete('/users', [UserManagementController::class, 'destroyMany'])->name('users.destroy-many');
    Route::get('/rooms', [RoomManagementController::class, 'index'])->name('rooms.index');
    Route::post('/rooms', [RoomManagementController::class, 'store'])->name('rooms.store');
    Route::put('/rooms/{room}', [RoomManagementController::class, 'update'])->name('rooms.update');
    Route::get('/banners', [BannerManagementController::class, 'index'])->name('banners.index');
    Route::post('/banners', [BannerManagementController::class, 'store'])->name('banners.store');
    Route::put('/banners/{section}/{bannerId}', [BannerManagementController::class, 'update'])
        ->where('section', 'sidebar|landing')
        ->name('banners.update');
    Route::delete('/banners/{section}/{bannerId}', [BannerManagementController::class, 'destroy'])
        ->where('section', 'sidebar|landing')
        ->name('banners.destroy');
});

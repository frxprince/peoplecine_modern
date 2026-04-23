<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        [$captchaToken, $captchaLength] = $this->issueChallenge($request);

        return view('auth.register', [
            'title' => $this->label('สมัครสมาชิก', 'Register'),
            'captchaToken' => $captchaToken,
            'captchaLength' => $captchaLength,
        ]);
    }

    public function captcha(Request $request): Response
    {
        $token = (string) $request->query('token', '');
        $storedToken = (string) $request->session()->get('registration.captcha_token', '');
        $answer = (string) $request->session()->get('registration.captcha_answer', '');

        abort_if($token === '' || $storedToken === '' || ! hash_equals($storedToken, $token) || $answer === '', 404);

        return response($this->renderCaptchaSvg($answer), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureNotRateLimited($request);

        $validated = validator($request->all(), [
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'display_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $password = (string) $value;

                    if (! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password) || ! preg_match('/\d/', $password) || ! preg_match('/[^A-Za-z0-9]/', $password)) {
                        $fail($this->label(
                            'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร และต้องมีตัวพิมพ์เล็ก ตัวพิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ',
                            'Password must be at least 8 characters and include uppercase, lowercase, number, and symbol characters.'
                        ));
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:0'],
            'captcha' => ['required', 'string', 'size:6'],
        ], $this->validationMessages(), $this->validationAttributes())->validate();

        $this->validateBotProtection($request, $validated);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'username' => trim($validated['username']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'password_reset_required' => false,
                'role' => 'user',
                'account_status' => 'active',
                'legacy_level' => 0,
            ]);

            UserProfile::query()->create([
                'user_id' => $user->id,
                'display_name' => trim($validated['display_name']),
                'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
                'province' => trim((string) ($validated['province'] ?? '')) ?: null,
                'postal_code' => trim((string) ($validated['postcode'] ?? '')) ?: null,
                'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            ]);

            return $user->fresh(['profile']);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $this->clearChallenge($request);
        RateLimiter::clear($this->throttleKey($request));

        return redirect()
            ->route('dashboard')
            ->with('status', $this->label('สร้างบัญชีสมาชิกใหม่เรียบร้อยแล้ว', 'Your new member account has been created.'));
    }

    private function issueChallenge(Request $request): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $answer = '';

        for ($index = 0; $index < 6; $index++) {
            $answer .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $token = (string) Str::uuid();

        $request->session()->put('registration.captcha_answer', $answer);
        $request->session()->put('registration.captcha_token', $token);
        $request->session()->put('registration.started_at', now()->timestamp);

        return [$token, strlen($answer)];
    }

    private function validateBotProtection(Request $request, array $validated): void
    {
        if (($validated['website'] ?? '') !== '') {
            $this->hitRateLimit($request);

            throw ValidationException::withMessages([
                'website' => $this->label('ไม่สามารถสมัครสมาชิกได้ กรุณาลองใหม่อีกครั้ง', 'Registration could not be completed. Please try again.'),
            ]);
        }

        $expectedAnswer = Str::upper((string) $request->session()->get('registration.captcha_answer', ''));
        if ($expectedAnswer === '' || Str::upper(trim((string) $validated['captcha'])) !== $expectedAnswer) {
            $this->issueChallenge($request);
            $this->hitRateLimit($request);

            throw ValidationException::withMessages([
                'captcha' => $this->label('กรุณากรอกข้อความจากภาพ CAPTCHA ให้ถูกต้อง', 'Please enter the CAPTCHA text correctly.'),
            ]);
        }

        $startedAt = (int) $request->session()->get('registration.started_at', 0);
        if ($startedAt === 0 || (now()->timestamp - $startedAt) < 3) {
            $this->issueChallenge($request);
            $this->hitRateLimit($request);

            throw ValidationException::withMessages([
                'captcha' => $this->label('กรุณาใช้เวลาสักครู่ก่อนส่งแบบฟอร์มสมัครสมาชิก', 'Please take a moment to complete the registration form.'),
            ]);
        }
    }

    private function ensureNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'username' => $this->label('มีการพยายามสมัครสมาชิกมากเกินไป กรุณารอสักครู่แล้วลองใหม่อีกครั้ง', 'Too many registration attempts. Please wait a few minutes and try again.'),
        ]);
    }

    private function hitRateLimit(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), 300);
    }

    private function throttleKey(Request $request): string
    {
        return 'register:'.Str::lower((string) $request->ip());
    }

    private function clearChallenge(Request $request): void
    {
        $request->session()->forget([
            'registration.challenge_answer',
            'registration.captcha_answer',
            'registration.captcha_token',
            'registration.started_at',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'username.required' => $this->label('กรุณากรอกชื่อผู้ใช้', 'Please enter a username.'),
            'username.min' => $this->label('ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร', 'Username must be at least 3 characters.'),
            'username.max' => $this->label('ชื่อผู้ใช้ต้องไม่เกิน 50 ตัวอักษร', 'Username may not be greater than 50 characters.'),
            'username.regex' => $this->label('ชื่อผู้ใช้ใช้ได้เฉพาะตัวอักษรอังกฤษ ตัวเลข จุด ขีดล่าง และขีดกลาง', 'Username may contain only letters, numbers, dots, underscores, and hyphens.'),
            'username.unique' => $this->label('ชื่อผู้ใช้นี้ถูกใช้งานแล้ว', 'This username is already taken.'),
            'display_name.required' => $this->label('กรุณากรอกชื่อที่แสดง', 'Please enter a display name.'),
            'display_name.max' => $this->label('ชื่อที่แสดงต้องไม่เกิน 100 ตัวอักษร', 'Display name may not be greater than 100 characters.'),
            'email.required' => $this->label('กรุณากรอกอีเมล', 'Please enter an email address.'),
            'email.email' => $this->label('รูปแบบอีเมลไม่ถูกต้อง', 'Please enter a valid email address.'),
            'email.max' => $this->label('อีเมลต้องไม่เกิน 255 ตัวอักษร', 'Email may not be greater than 255 characters.'),
            'email.unique' => $this->label('อีเมลนี้ถูกใช้งานแล้ว', 'This email address is already registered.'),
            'password.required' => $this->label('กรุณากรอกรหัสผ่าน', 'Please enter a password.'),
            'password.min' => $this->label('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร', 'Password must be at least 8 characters.'),
            'password.max' => $this->label('รหัสผ่านต้องไม่เกิน 255 ตัวอักษร', 'Password may not be greater than 255 characters.'),
            'password.confirmed' => $this->label('การยืนยันรหัสผ่านไม่ตรงกัน', 'Password confirmation does not match.'),
            'phone.max' => $this->label('เบอร์โทรต้องไม่เกิน 50 ตัวอักษร', 'Phone may not be greater than 50 characters.'),
            'province.max' => $this->label('จังหวัดต้องไม่เกิน 100 ตัวอักษร', 'Province may not be greater than 100 characters.'),
            'postcode.max' => $this->label('รหัสไปรษณีย์ต้องไม่เกิน 20 ตัวอักษร', 'Postcode may not be greater than 20 characters.'),
            'address.max' => $this->label('ที่อยู่ต้องไม่เกิน 2000 ตัวอักษร', 'Address may not be greater than 2000 characters.'),
            'captcha.required' => $this->label('กรุณากรอกข้อความจากภาพ CAPTCHA', 'Please enter the CAPTCHA text.'),
            'captcha.size' => $this->label('กรุณากรอก CAPTCHA ให้ครบ 6 ตัวอักษร', 'Please enter all 6 CAPTCHA characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'username' => $this->label('ชื่อผู้ใช้', 'username'),
            'display_name' => $this->label('ชื่อที่แสดง', 'display name'),
            'email' => $this->label('อีเมล', 'email'),
            'password' => $this->label('รหัสผ่าน', 'password'),
            'phone' => $this->label('โทรศัพท์', 'phone'),
            'province' => $this->label('จังหวัด', 'province'),
            'postcode' => $this->label('รหัสไปรษณีย์', 'postcode'),
            'address' => $this->label('ที่อยู่', 'address'),
            'captcha' => 'CAPTCHA',
        ];
    }

    private function label(string $thai, string $english): string
    {
        return app()->getLocale() === 'th' ? $thai : $english;
    }

    private function renderCaptchaSvg(string $answer): string
    {
        $width = 220;
        $height = 74;
        $backgrounds = ['#f7fbff', '#eff6ff', '#fdf8ec'];
        $strokePalette = ['#9eb6d8', '#c86d5a', '#85a78b', '#6d86b3'];
        $textPalette = ['#18417d', '#6d1f16', '#314f1d', '#4b2f74'];

        $svg = [];
        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" role="img" aria-label="CAPTCHA">';
        $svg[] = '<rect width="100%" height="100%" rx="8" fill="'.$backgrounds[random_int(0, count($backgrounds) - 1)].'"/>';

        for ($index = 0; $index < 7; $index++) {
            $stroke = $strokePalette[random_int(0, count($strokePalette) - 1)];
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $svg[] = '<path d="M'.$x1.' '.$y1.' Q '.random_int(0, $width).' '.random_int(0, $height).' '.$x2.' '.$y2.'" stroke="'.$stroke.'" stroke-width="'.random_int(1, 2).'" fill="none" opacity="0.55"/>';
        }

        for ($index = 0; $index < 28; $index++) {
            $fill = $strokePalette[random_int(0, count($strokePalette) - 1)];
            $svg[] = '<circle cx="'.random_int(8, $width - 8).'" cy="'.random_int(8, $height - 8).'" r="'.random_int(1, 2).'" fill="'.$fill.'" opacity="0.45"/>';
        }

        $chars = str_split($answer);
        foreach ($chars as $index => $char) {
            $x = 24 + ($index * 31) + random_int(-2, 4);
            $y = 46 + random_int(-5, 6);
            $rotation = random_int(-22, 22);
            $fill = $textPalette[random_int(0, count($textPalette) - 1)];
            $fontSize = random_int(25, 31);
            $svg[] = '<text x="'.$x.'" y="'.$y.'" font-family="Tahoma, Verdana, Arial, sans-serif" font-size="'.$fontSize.'" font-weight="700" fill="'.$fill.'" transform="rotate('.$rotation.' '.$x.' '.$y.')">'.$char.'</text>';
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }
}

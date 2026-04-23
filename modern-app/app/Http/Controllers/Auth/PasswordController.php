<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.password-edit', [
            'title' => $this->label('เปลี่ยนรหัสผ่าน', 'Choose a New Password'),
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $password = (string) $value;

                    if (
                        ! preg_match('/[a-z]/', $password)
                        || ! preg_match('/[A-Z]/', $password)
                        || ! preg_match('/\d/', $password)
                        || ! preg_match('/[^A-Za-z\d]/', $password)
                    ) {
                        $fail($this->label(
                            'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร และประกอบด้วยตัวพิมพ์เล็ก ตัวพิมพ์ใหญ่ ตัวเลข และสัญลักษณ์อย่างน้อยอย่างละ 1 ตัว',
                            'The new password must be at least 8 characters and include at least one lowercase letter, one uppercase letter, one number, and one symbol.'
                        ));
                    }
                },
            ],
        ], $this->validationMessages(), $this->validationAttributes());

        if (! $user->requiresPasswordReset()) {
            if (! Hash::check((string) ($validated['current_password'] ?? ''), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => $this->label(
                        'รหัสผ่านปัจจุบันไม่ถูกต้องสำหรับบัญชีนี้',
                        'The current password does not match this account.'
                    ),
                ]);
            }
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_reset_required' => false,
            'remember_token' => null,
        ])->save();

        return redirect()
            ->route('dashboard')
            ->with('status', $this->label('อัปเดตรหัสผ่านเรียบร้อยแล้ว', 'Your password has been updated.'));
    }

    private function validationMessages(): array
    {
        return [
            'current_password.required' => $this->label('กรุณากรอกรหัสผ่านปัจจุบัน', 'Please enter your current password.'),
            'password.required' => $this->label('กรุณากรอกรหัสผ่านใหม่', 'Please enter a new password.'),
            'password.min' => $this->label('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร', 'The new password must be at least 8 characters.'),
            'password.max' => $this->label('รหัสผ่านใหม่ต้องยาวไม่เกิน 255 ตัวอักษร', 'The new password may not be greater than 255 characters.'),
            'password.confirmed' => $this->label('การยืนยันรหัสผ่านใหม่ไม่ตรงกัน', 'The new password confirmation does not match.'),
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'current_password' => $this->label('รหัสผ่านปัจจุบัน', 'current password'),
            'password' => $this->label('รหัสผ่านใหม่', 'new password'),
            'password_confirmation' => $this->label('ยืนยันรหัสผ่านใหม่', 'new password confirmation'),
        ];
    }

    private function label(string $thai, string $english): string
    {
        return app()->getLocale() === 'th' ? $thai : $english;
    }
}

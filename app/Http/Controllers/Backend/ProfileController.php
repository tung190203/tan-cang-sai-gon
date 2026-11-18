<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;

class ProfileController extends Controller
{
    private const ALLOWED_AVATAR_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    private const MAX_AVATAR_BYTES = 2 * 1024 * 1024;

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|string',
            'avatar_file' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:2048',
            'current_password' => 'nullable|string',
            'password' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).+$/'],
        ];

        $messages = [
            'name.required' => 'Họ và tên không được để trống.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã tồn tại trên hệ thống.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.regex' => 'Mật khẩu phải chứa ít nhất 1 chữ viết hoa và 1 ký tự đặc biệt.',
        ];

        $validated = $request->validate($rules, $messages);

        // Check current password if attempting to change password
        if ($request->filled('password')) {
            if (!$request->filled('current_password')) {
                return response()->json([
                    'errors' => ['current_password' => ['Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu mới.']]
                ], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'errors' => ['current_password' => ['Mật khẩu hiện tại không chính xác.']]
                ], 422);
            }
        }

        $user->name = trim(strip_tags($request->name));
        $user->email = trim(strip_tags($request->email));

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Handle Avatar
        if ($request->filled('avatar') && preg_match('/^data:image\/([a-zA-Z0-9]+);base64,/', $request->avatar, $type)) {
            $extension = strtolower($type[1]);
            if (! in_array($extension, self::ALLOWED_AVATAR_EXTENSIONS, true)) {
                return response()->json([
                    'errors' => ['avatar' => ['Định dạng ảnh đại diện không hợp lệ (chỉ hỗ trợ PNG, JPG, JPEG, WEBP).']]
                ], 422);
            }

            $avatarData = base64_decode(substr($request->avatar, strpos($request->avatar, ',') + 1), true);
            if ($avatarData === false || strlen($avatarData) > self::MAX_AVATAR_BYTES || @getimagesizefromstring($avatarData) === false) {
                return response()->json([
                    'errors' => ['avatar' => ['Dữ liệu ảnh đại diện không hợp lệ hoặc vượt quá 2MB.']]
                ], 422);
            }

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $fileName = 'avatars/' . Str::random(40) . '.' . $extension;
            Storage::disk('public')->put($fileName, $avatarData);
            $user->avatar = $fileName;
        } elseif ($request->hasFile('avatar_file')) {
            $file = $request->file('avatar_file');
            $extension = strtolower($file->guessExtension() ?: $file->extension());
            if (! in_array($extension, self::ALLOWED_AVATAR_EXTENSIONS, true)) {
                return response()->json([
                    'errors' => ['avatar_file' => ['Định dạng ảnh đại diện không hợp lệ.']]
                ], 422);
            }

            $fileName = 'avatars/' . Str::random(40) . '.' . $extension;
            Storage::disk('public')->putFileAs('avatars', $file, basename($fileName));

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $fileName;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin cá nhân thành công!',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : asset('backend_assets/images/logo.png'),
            ]
        ]);
    }
}

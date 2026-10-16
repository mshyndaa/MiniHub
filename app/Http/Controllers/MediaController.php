<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Profile photo and banner: upload, remove, and serve.
 * Files live on the private "local" disk and are only served to signed-in users.
 */
class MediaController extends Controller
{
    private const KINDS = ['avatar' => 'avatar_path', 'banner' => 'banner_path', 'tvbg' => 'tv_bg_path'];

    public function show(User $user, string $kind)
    {
        abort_unless(isset(self::KINDS[$kind]), 404);
        $path = $user->{self::KINDS[$kind]};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function update(Request $request, string $kind)
    {
        abort_unless(isset(self::KINDS[$kind]), 404);

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'image.max' => 'The image must be 5 MB or smaller.',
            'image.uploaded' => 'The image could not be uploaded. Make sure it is 5 MB or smaller.',
            'image.image' => 'Please choose an image file (JPG, PNG, WebP or GIF).',
            'image.mimes' => 'Please choose a JPG, PNG, WebP or GIF image.',
        ]);

        $user = $request->user();
        $column = self::KINDS[$kind];

        if ($user->$column) {
            Storage::disk('local')->delete($user->$column);
        }
        $user->update([$column => $request->file('image')->store("profile/{$kind}s", 'local')]);

        if ($request->expectsJson()) {
            return response()->json(['url' => $user->tvBackgroundUrl()]);
        }

        return back()->with('status', ucfirst($kind) === 'Avatar' ? 'Profile photo updated.' : 'Banner updated.');
    }

    /** Pick one of the built-in cartoon avatars. */
    public function preset(Request $request)
    {
        $data = $request->validate(['preset' => ['required', Rule::in(User::avatarKeys())]]);
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
        }
        $user->update(['avatar_preset' => $data['preset'], 'avatar_path' => null]);

        return back()->with('status', 'Avatar updated.');
    }

    /** Pick one of the built-in banners. */
    public function bannerPreset(Request $request)
    {
        $data = $request->validate(['preset' => ['required', Rule::in(array_keys(config('banners')))]]);
        $user = $request->user();

        if ($user->banner_path) {
            Storage::disk('local')->delete($user->banner_path);
        }
        $user->update(['banner_preset' => $data['preset'], 'banner_path' => null]);

        return back()->with('status', 'Banner updated.');
    }

    public function destroy(Request $request, string $kind)
    {
        abort_unless(isset(self::KINDS[$kind]), 404);

        $user = $request->user();
        $column = self::KINDS[$kind];

        if ($kind === 'banner' && $user->banner_preset) {
            $user->update(['banner_preset' => null]);
        }
        if ($kind === 'avatar' && $user->avatar_preset) {
            $user->update(['avatar_preset' => null]);
        }
        if ($user->$column) {
            Storage::disk('local')->delete($user->$column);
            $user->update([$column => null]);
        }

        if ($request->expectsJson()) {
            return response()->json(['url' => null]);
        }

        return back()->with('status', ucfirst($kind) === 'Avatar' ? 'Profile photo removed.' : 'Banner removed.');
    }
}

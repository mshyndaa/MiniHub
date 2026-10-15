<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $since = $user->notifications_read_at ?? $user->created_at;

        $items = Announcement::with('author:id,name')->latest('id')->paginate(10);

        // Remember what was new on this visit, then mark everything as read.
        $newIds = $items->getCollection()->where('created_at', '>', $since)->pluck('id')->all();
        $user->forceFill(['notifications_read_at' => now()])->save();

        return view('notifications', ['items' => $items, 'newIds' => $newIds]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Announcement::create($data + ['user_id' => $request->user()->id]);

        return redirect()->route('notifications.index')->with('status', 'Notification sent to all users.');
    }

    public function destroy(int $id)
    {
        Announcement::findOrFail($id)->delete();

        return back()->with('status', 'Notification deleted.');
    }
}

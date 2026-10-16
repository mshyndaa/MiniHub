<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /** Customer Service screen: one big button per item. */
    public function index()
    {
        return view('stock.index', ['items' => $this->items()]);
    }

    /** Full-screen display for the TV. */
    public function tv()
    {
        $user = auth()->user();

        return view('stock.tv', [
            'items' => $this->items(),
            'background' => $user->tvBackgroundUrl(),
            'layout' => $user->tv_layout,
            'style' => $user->tv_style,
        ]);
    }

    /** Save this user's tile positions/sizes (percent of the grid area), keyed by item id. */
    public function saveLayout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'layout' => ['required', 'array', 'max:100'],
            'layout.*.x' => ['required', 'numeric', 'between:0,100'],
            'layout.*.y' => ['required', 'numeric', 'between:0,100'],
            'layout.*.w' => ['required', 'numeric', 'between:5,100'],
            'layout.*.h' => ['required', 'numeric', 'between:5,100'],
        ]);

        $layout = [];
        foreach ($data['layout'] as $id => $box) {
            $layout[(int) $id] = array_map('floatval', array_intersect_key($box, array_flip(['x', 'y', 'w', 'h'])));
        }
        $request->user()->update(['tv_layout' => $layout]);

        return response()->json(['ok' => true]);
    }

    /** Save this user's tile look: text colour, tile colour and tile opacity (0-100). */
    public function saveStyle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tile' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'opacity' => ['nullable', 'integer', 'between:0,100'],
            'overlay' => ['nullable', 'boolean'],
            'scale' => ['nullable', 'integer', 'between:40,200'],
        ]);
        $request->user()->update(['tv_style' => [
            'text' => $data['text'] ?? null,
            'tile' => $data['tile'] ?? null,
            'opacity' => $data['opacity'] ?? null,
            'overlay' => $data['overlay'] ?? true,
            'scale' => $data['scale'] ?? null,
        ]]);

        return response()->json(['ok' => true]);
    }

    public function resetStyle(Request $request): JsonResponse
    {
        $request->user()->update(['tv_style' => null]);

        return response()->json(['ok' => true]);
    }

    public function resetLayout(Request $request): JsonResponse
    {
        $request->user()->update(['tv_layout' => null]);

        return response()->json(['ok' => true]);
    }

    /** Live numbers, polled by the TV and the Customer Service screen. */
    public function data(): JsonResponse
    {
        return response()->json($this->items());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $mine = $request->user()->stockItems();
        $item = $mine->create($data + ['sort_order' => (int) $mine->max('sort_order') + 1]);

        return back()->with('status', "{$item->name} added.");
    }

    public function update(Request $request, int $id)
    {
        $item = $request->user()->stockItems()->findOrFail($id);
        $item->update($this->validated($request));

        return back()->with('status', "{$item->name} updated.");
    }

    public function destroy(Request $request, int $id)
    {
        $item = $request->user()->stockItems()->findOrFail($id);
        $item->delete();

        return back()->with('status', "{$item->name} removed.");
    }

    /** One customer scanned the barcode: stock goes down by one (delta -1), or undo (+1). */
    public function adjust(Request $request, int $id): JsonResponse
    {
        $delta = $request->validate(['delta' => ['required', 'integer', 'in:-1,1']])['delta'];
        $query = StockItem::whereKey($request->user()->stockItems()->findOrFail($id)->id);

        // Single atomic UPDATE so two clicks at once never lose a count, and stock never goes below 0.
        $delta < 0 ? $query->where('stock', '>', 0)->decrement('stock') : $query->increment('stock');

        return response()->json($this->items());
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:16'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
        $data['name'] ??= '';

        return $data;
    }

    private function items(): array
    {
        return auth()->user()->stockItems()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'icon', 'stock'])->toArray();
    }
}

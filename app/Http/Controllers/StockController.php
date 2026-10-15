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
        return view('stock.tv', ['items' => $this->items()]);
    }

    /** Live numbers, polled by the TV and the Customer Service screen. */
    public function data(): JsonResponse
    {
        return response()->json($this->items());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $item = StockItem::create($data + ['sort_order' => (int) StockItem::max('sort_order') + 1]);

        return back()->with('status', "{$item->name} added.");
    }

    public function update(Request $request, int $id)
    {
        $item = StockItem::findOrFail($id);
        $item->update($this->validated($request));

        return back()->with('status', "{$item->name} updated.");
    }

    public function destroy(int $id)
    {
        $item = StockItem::findOrFail($id);
        $item->delete();

        return back()->with('status', "{$item->name} removed.");
    }

    /** One customer scanned the barcode: stock goes down by one (delta -1), or undo (+1). */
    public function adjust(Request $request, int $id): JsonResponse
    {
        $delta = $request->validate(['delta' => ['required', 'integer', 'in:-1,1']])['delta'];
        $query = StockItem::whereKey(StockItem::findOrFail($id)->id);

        // Single atomic UPDATE so two clicks at once never lose a count, and stock never goes below 0.
        $delta < 0 ? $query->where('stock', '>', 0)->decrement('stock') : $query->increment('stock');

        return response()->json($this->items());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:16'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
    }

    private function items(): array
    {
        return StockItem::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'icon', 'stock'])->toArray();
    }
}

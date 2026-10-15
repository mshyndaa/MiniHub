<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SpinController extends Controller
{
    private const KEY = 'spin.tv';

    /** Control screen: names are loaded and drawn in the browser. */
    public function index()
    {
        return view('spin.index');
    }

    /** Full-screen, wheel-only display for a TV. */
    public function tv()
    {
        return view('spin.tv');
    }

    /** What the TV should show right now. */
    public function state(): JsonResponse
    {
        return response()->json(Cache::get(self::KEY, ['v' => 0, 'names' => [], 'picked' => [], 'history' => [], 'added' => 0, 'plan' => null]));
    }

    /** The control screen publishes its wheel (and any spin it is about to play) for the TV. */
    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'names' => ['present', 'array', 'max:5000'],
            'names.*' => ['string', 'max:100'],
            'picked' => ['present', 'array'],
            'picked.*' => ['integer', 'min:0'],
            'history' => ['present', 'array', 'max:5000'],
            'history.*' => ['string', 'max:100'],
            'added' => ['integer', 'min:0'],
            'plan' => ['nullable', 'array'],
            'plan.id' => ['required_with:plan', 'string', 'max:40'],
            'plan.steps' => ['required_with:plan', 'array', 'max:5000'],
            'plan.steps.*.pool' => ['required', 'array'],
            'plan.steps.*.pool.*' => ['integer', 'min:0'],
            'plan.steps.*.k' => ['required', 'integer', 'min:0'],
            'plan.steps.*.j' => ['required', 'numeric'],
            'plan.steps.*.ms' => ['required', 'integer', 'min:0', 'max:20000'],
            'plan.steps.*.turns' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $data['added'] ??= 0;
        $data['plan'] ??= null;
        $data['v'] = (int) (microtime(true) * 1000);
        Cache::put(self::KEY, $data, now()->addHours(12));

        return response()->json(['ok' => true]);
    }
}

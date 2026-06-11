<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pocket;
use App\Models\ShoppingItem;
use Illuminate\Http\Request;

class ShoppingController extends Controller
{
    public function store(Request $request, $pocketId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'unit_price' => 'required|integer|min:0',
            'person_count' => 'required|integer|min:1',
            'category' => 'nullable|string|max:64',
        ]);

        $pocket = Pocket::findOrFail($pocketId);
        abort_unless($pocket->user_id == $request->user()->id, 403, 'Only the pocket owner can manage the shopping list.');

        ShoppingItem::create([
            'pocket_id' => $pocket->id,
            'name' => $data['name'],
            'unit_price' => $data['unit_price'],
            'person_count' => $data['person_count'],
            'category' => $data['category'] ?? null,
        ]);

        return back()->with('status', 'Item added to the shopping list.');
    }

    public function destroy(Request $request, $id)
    {
        $item = ShoppingItem::findOrFail($id);
        $pocket = Pocket::find($item->pocket_id);
        abort_unless($pocket && $pocket->user_id == $request->user()->id, 403);

        $item->delete();

        return back()->with('status', 'Item removed.');
    }
}

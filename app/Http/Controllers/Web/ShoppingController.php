<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pocket;
use App\Models\PocketSlot;
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
        $user = $request->user();
        $isOwner = $pocket->user_id == $user->id;
        // Members may suggest items only while the admin has opened suggestions.
        $isActiveMember = PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id, 'status' => 1])->exists();
        abort_unless($isOwner || ($pocket->open_purchasing_item && $isActiveMember), 403, 'Shopping suggestions are closed for this pocket.');

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

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\Pocket;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $pockets = collect();
        $adashis = collect();

        if ($q !== '') {
            // Discoverable (open) pockets + the user's own.
            $pockets = Pocket::where('title', 'LIKE', "%$q%")
                ->where(fn ($w) => $w->where('status', 1)->orWhere('user_id', auth()->id()))
                ->orderByDesc('id')->limit(25)->get();

            $adashis = Adashi::where('name', 'LIKE', "%$q%")
                ->where(fn ($w) => $w->where('is_public', true)->orWhere('admin_id', auth()->id()))
                ->orderByDesc('id')->limit(25)->get();
        }

        return view('search', compact('q', 'pockets', 'adashis'));
    }
}

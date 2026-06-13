<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CharityProject;
use App\Models\Pocket;
use App\Services\Charity\CharityService;
use Illuminate\Http\Request;

class CharityController extends Controller
{
    public function __construct(private CharityService $charity)
    {
    }

    /** Admin: set up / edit the pocket's charity drive. */
    public function setup($pocketId)
    {
        $pocket = Pocket::findOrFail($pocketId);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket admin can set up charity.');
        abort_unless($this->charity->enabled(), 404);

        $project = CharityProject::where('pocket_id', $pocket->id)->latest('id')->first();
        $goalItems = $project ? $project->goalItems()->get() : collect();

        return view('charity.setup', compact('pocket', 'project', 'goalItems'));
    }

    /** Admin: create / update the drive. */
    public function store(Request $request, $pocketId)
    {
        $pocket = Pocket::findOrFail($pocketId);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket admin can set up charity.');
        abort_unless($this->charity->enabled(), 404);

        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'donors_visible' => 'nullable|boolean',
            'title' => 'required_with:enabled|string|max:255',
            'description' => 'nullable|string|max:2000',
            'goal_type' => 'required|in:amount,items',
            'target_amount' => 'nullable|integer|min:0',
            'items' => 'nullable|array',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.unit' => 'nullable|string|max:32',
            'items.*.target_quantity' => 'nullable|integer|min:0',
            'items.*.unit_price' => 'nullable|integer|min:0',
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['donors_visible'] = $request->boolean('donors_visible');
        $this->charity->configureProject($pocket, $data);

        return redirect()->route('pockets.show', $pocket->id)->with('status', 'Charity drive updated.');
    }

    /** Member: record a donation (money and/or items). */
    public function donate(Request $request, $pocketId)
    {
        $pocket = Pocket::findOrFail($pocketId);
        abort_unless($this->charity->enabled() && $pocket->charity_enabled, 404, 'Charity is not enabled for this pocket.');

        $project = $this->charity->activeProject($pocket);
        abort_unless($project, 422, 'There is no active charity drive.');

        $data = $request->validate([
            'amount' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.goal_item_id' => 'nullable|integer',
            'items.*.quantity' => 'nullable|integer|min:0',
        ]);

        $this->charity->recordDonation(
            $pocket, $project, $request->user(),
            (int) ($data['amount'] ?? 0),
            $data['items'] ?? [],
            $data['note'] ?? null
        );

        return back()->with('status', 'JazakAllahu khairan — your donation was recorded.')->with('celebrate', true);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pocket;
use App\Services\Charity\CharityService;
use Illuminate\Http\Request;

class CharityController extends Controller
{
    public function __construct(private CharityService $charity)
    {
    }

    /** GET /api/pocket/charity?id= — privacy-aware charity summary for the viewer. */
    public function show(Request $request)
    {
        if (!$this->charity->enabled()) {
            return ['enabled' => false];
        }

        $pocket = Pocket::find($request->id);
        if (!$pocket || !$pocket->charity_enabled) {
            return ['enabled' => false];
        }

        $project = $this->charity->activeProject($pocket);
        if (!$project) {
            return ['enabled' => true, 'project' => null];
        }

        $user = $request->user();
        $isAdmin = $pocket->user_id == $user->id;

        return array_merge(['enabled' => true], $this->charity->summary($pocket, $project, $user, $isAdmin));
    }

    /** POST /api/pocket/charity/donate — member records a donation. */
    public function donate(Request $request)
    {
        $pocket = Pocket::find($request->id);
        if (!$this->charity->enabled() || !$pocket || !$pocket->charity_enabled) {
            return response(['message' => 'Charity is not enabled for this pocket.'], 404);
        }

        $project = $this->charity->activeProject($pocket);
        if (!$project) {
            return response(['message' => 'There is no active charity drive.'], 422);
        }

        $data = $request->validate([
            'amount' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.goal_item_id' => 'nullable|integer',
            'items.*.quantity' => 'nullable|integer|min:0',
        ]);

        $invoice = $this->charity->recordDonation(
            $pocket, $project, $request->user(),
            (int) ($data['amount'] ?? 0), $data['items'] ?? [], $data['note'] ?? null
        );

        return response(['message' => 'Donation recorded.', 'invoice_id' => $invoice->id]);
    }

    /** POST /api/pocket/charity/setup — admin configures the drive. */
    public function setup(Request $request)
    {
        $pocket = Pocket::find($request->id);
        if (!$this->charity->enabled() || !$pocket) {
            return response(['message' => 'Not available.'], 404);
        }
        if ($pocket->user_id != $request->user()->id) {
            return response(['message' => 'Only the pocket admin can set up charity.'], 403);
        }

        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'donors_visible' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'goal_type' => 'required|in:amount,items',
            'target_amount' => 'nullable|integer|min:0',
            'items' => 'nullable|array',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.unit' => 'nullable|string|max:32',
            'items.*.target_quantity' => 'nullable|integer|min:0',
            'items.*.unit_price' => 'nullable|integer|min:0',
        ]);

        $project = $this->charity->configureProject($pocket, $data);

        return response(['message' => 'Charity drive updated.', 'project_id' => $project->id]);
    }
}

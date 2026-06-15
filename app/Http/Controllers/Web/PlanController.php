<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\User;
use App\Services\Plan\PlanService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private PlanService $plans)
    {
    }

    public function index()
    {
        $userId = auth()->id();
        $owned = Plan::where('owner_id', $userId)->latest('id')->get();
        $shared = Plan::whereHas('collaborators', fn ($q) => $q->where('users.id', $userId))->latest('id')->get();

        return view('plans.index', compact('owned', 'shared'));
    }

    public function create()
    {
        // Offer to carry deferred items from a previous plan into the new one.
        $carrySource = Plan::where('owner_id', auth()->id())
            ->whereHas('items', fn ($q) => $q->where('status', 'deferred'))
            ->latest('id')->first();

        return view('plans.create', compact('carrySource'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'period_type' => 'nullable|in:month,year',
            'month' => 'nullable|string|max:7',  // "2026-06" (month) or "2026" (year)
            'budget' => 'nullable|integer|min:0',
            'carry_from' => 'nullable|integer',
        ]);

        $plan = Plan::create([
            'owner_id' => auth()->id(),
            'title' => $data['title'],
            'period_type' => $data['period_type'] ?? 'month',
            'month' => ($data['month'] ?? null) ?: null,
            'budget' => $data['budget'] ?? null,
            'status' => 'ACTIVE',
        ]);

        if (!empty($data['carry_from'])) {
            $from = Plan::find($data['carry_from']);
            if ($from && $from->owner_id == auth()->id()) {
                $n = $this->plans->carryOverDeferred($from, $plan, auth()->id());
                if ($n) {
                    return redirect()->route('plans.show', $plan->id)
                        ->with('status', "Plan created. Carried over {$n} deferred item(s) — marked priority.");
                }
            }
        }

        return redirect()->route('plans.show', $plan->id)->with('status', 'Plan created.');
    }

    public function show($id)
    {
        $plan = $this->accessiblePlan($id);
        $items = $plan->items()->orderByDesc('priority')->orderBy('status')->orderBy('id')->get();
        $summary = $this->plans->summary($plan);
        $collaborators = $plan->collaborators()->get();
        $isOwner = $plan->owner_id == auth()->id();

        // Friends you can one-click share with (accepted, not already on the plan).
        $friends = collect();
        if ($isOwner) {
            $me = auth()->id();
            $friendIds = \App\Models\Friendship::where('status', 'accepted')
                ->where(fn ($q) => $q->where('user_id', $me)->orWhere('friend_id', $me))
                ->get()->map(fn ($f) => $f->user_id == $me ? $f->friend_id : $f->user_id)->unique();
            $friends = User::whereIn('id', $friendIds)
                ->whereNotIn('id', $collaborators->pluck('id'))
                ->where('id', '!=', $plan->owner_id)
                ->orderBy('name')->get();
        }

        return view('plans.show', compact('plan', 'items', 'summary', 'collaborators', 'isOwner', 'friends'));
    }

    public function storeItem(Request $request, $id)
    {
        $plan = $this->accessiblePlan($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:32',
            'unit_price' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        PlanItem::create([
            'plan_id' => $plan->id,
            'name' => $data['name'],
            'quantity' => $data['quantity'] ?? 1,
            'unit' => $data['unit'] ?? null,
            'unit_price' => $data['unit_price'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Item added.');
    }

    public function updateItem(Request $request, $itemId)
    {
        $item = PlanItem::findOrFail($itemId);
        $plan = $this->accessiblePlan($item->plan_id);
        $action = $request->input('action');

        switch ($action) {
            case 'purchased':
                $item->status = 'purchased';
                $item->purchased_at = now();
                break;
            case 'deferred':
                $item->status = 'deferred';
                $item->purchased_at = null;
                break;
            case 'pending':
                $item->status = 'pending';
                $item->purchased_at = null;
                break;
            case 'claim':
                // Toggle: claim for myself, or release if I already hold it.
                $item->claimed_by = $item->claimed_by == auth()->id() ? null : auth()->id();
                break;
            case 'edit':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'quantity' => 'nullable|integer|min:1',
                    'unit' => 'nullable|string|max:32',
                    'unit_price' => 'nullable|integer|min:0',
                    'note' => 'nullable|string|max:255',
                ]);
                $item->fill([
                    'name' => $data['name'],
                    'quantity' => $data['quantity'] ?? 1,
                    'unit' => $data['unit'] ?? null,
                    'unit_price' => $data['unit_price'] ?? null,
                    'note' => $data['note'] ?? null,
                ]);
                break;
            default:
                abort(422, 'Unknown action.');
        }

        $item->save();

        return back()->with('status', 'Updated.');
    }

    public function destroyItem($itemId)
    {
        $item = PlanItem::findOrFail($itemId);
        $this->accessiblePlan($item->plan_id);
        $item->delete();

        return back()->with('status', 'Item removed.');
    }

    public function share(Request $request, $id)
    {
        $plan = $this->accessiblePlan($id);
        abort_unless($plan->owner_id == auth()->id(), 403, 'Only the plan owner can share it.');

        $data = $request->validate(['friend_id' => 'required|integer']);
        $me = auth()->id();

        // You can only share with someone who is your accepted friend.
        $isFriend = \App\Models\Friendship::where('status', 'accepted')
            ->where(function ($q) use ($me, $data) {
                $q->where(['user_id' => $me, 'friend_id' => $data['friend_id']])
                  ->orWhere(['user_id' => $data['friend_id'], 'friend_id' => $me]);
            })->exists();

        if (!$isFriend) {
            return back()->withErrors(['friend_id' => 'You can only share with your friends. Add them on the Friends page first.']);
        }

        $plan->collaborators()->syncWithoutDetaching([$data['friend_id']]);
        $user = User::find($data['friend_id']);

        return back()->with('status', ($user->name ?? 'Your friend').' can now collaborate on this plan.');
    }

    public function unshare($id, $userId)
    {
        $plan = $this->accessiblePlan($id);
        abort_unless($plan->owner_id == auth()->id(), 403, 'Only the plan owner can manage sharing.');
        $plan->collaborators()->detach($userId);

        return back()->with('status', 'Collaborator removed.');
    }

    public function archive($id)
    {
        $plan = $this->accessiblePlan($id);
        abort_unless($plan->owner_id == auth()->id(), 403, 'Only the plan owner can archive it.');
        $plan->update(['status' => 'ARCHIVED']);

        return redirect()->route('plans.index')->with('status', 'Plan archived.');
    }

    /** Resolve a plan the current user owns or collaborates on, or 403/404. */
    private function accessiblePlan($id): Plan
    {
        $plan = Plan::findOrFail($id);
        abort_unless($plan->accessibleBy(auth()->id()), 403, 'You do not have access to this plan.');

        return $plan;
    }
}

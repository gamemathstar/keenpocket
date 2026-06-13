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
            'month' => 'nullable|string|max:7',
            'budget' => 'nullable|integer|min:0',
            'carry_from' => 'nullable|integer',
        ]);

        $plan = Plan::create([
            'owner_id' => auth()->id(),
            'title' => $data['title'],
            'month' => $data['month'] ?? null,
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

        return view('plans.show', compact('plan', 'items', 'summary', 'collaborators', 'isOwner'));
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

        $data = $request->validate(['contact' => 'required|string|max:255']);
        $contact = trim($data['contact']);

        $user = User::where('email', $contact)->first()
            ?? User::where('phone_number', 'LIKE', '%'.PhoneNumber::normalize($contact).'%')->first();

        if (!$user) {
            return back()->withErrors(['contact' => 'No KeenPocket user found with that phone or email.']);
        }
        if ($user->id == $plan->owner_id) {
            return back()->withErrors(['contact' => 'You already own this plan.']);
        }

        $plan->collaborators()->syncWithoutDetaching([$user->id]);

        return back()->with('status', $user->name.' can now collaborate on this plan.');
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

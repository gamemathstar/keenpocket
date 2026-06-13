<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\Dispute;
use App\Models\Pocket;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    /** A member raises a dispute on a pocket or adashi. */
    public function raise(Request $request, $type, $id)
    {
        abort_unless(config('disputes.enabled', true) && in_array($type, ['pocket', 'adashi'], true), 404);
        abort_unless(ChatController::canAccess($type, (int) $id, $request->user()->id), 403, 'Only members can raise a dispute.');

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
        ]);

        Dispute::create([
            'context_type' => $type, 'context_id' => (int) $id, 'raised_by' => $request->user()->id,
            'subject' => $data['subject'], 'body' => $data['body'], 'status' => 'OPEN',
        ]);

        return back()->with('status', 'Dispute raised — the admin will review it.');
    }

    public function resolve(Request $request, $disputeId)
    {
        return $this->close($request, $disputeId, 'RESOLVED');
    }

    public function dismiss(Request $request, $disputeId)
    {
        return $this->close($request, $disputeId, 'DISMISSED');
    }

    private function close(Request $request, $disputeId, string $status)
    {
        $dispute = Dispute::findOrFail($disputeId);
        abort_unless($this->isAdmin($dispute, $request->user()->id), 403, 'Only the admin can resolve disputes.');

        $data = $request->validate(['resolution' => 'nullable|string|max:2000']);
        $dispute->update([
            'status' => $status,
            'resolution' => $data['resolution'] ?? null,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        if ($dispute->context_type === 'adashi') {
            try {
                \App\Models\AdashiAuditLog::create([
                    'adashi_id' => $dispute->context_id, 'user_id' => $request->user()->id,
                    'action' => 'DISPUTE_'.$status, 'meta' => json_encode(['dispute_id' => $dispute->id]),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return back()->with('status', 'Dispute '.strtolower($status).'.');
    }

    /** Is this user the admin of the dispute's pocket/adashi? */
    private function isAdmin(Dispute $dispute, int $userId): bool
    {
        if ($dispute->context_type === 'pocket') {
            return (bool) Pocket::where(['id' => $dispute->context_id, 'user_id' => $userId])->exists();
        }

        return (bool) Adashi::where(['id' => $dispute->context_id, 'admin_id' => $userId])->exists();
    }
}

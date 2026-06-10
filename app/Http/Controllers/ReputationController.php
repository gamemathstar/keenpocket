<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Reputation\ReputationService;
use Illuminate\Http\Request;

class ReputationController extends Controller
{
    public function __construct(private ReputationService $reputation)
    {
    }

    /**
     * Reputation for a user — shown before joining a group they organise.
     */
    public function show(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response(['message' => 'User not found.'], 404);
        }

        return response([
            'user' => ['id' => $user->id, 'name' => $user->name],
            'reputation' => $this->reputation->forUser($user->id),
        ]);
    }

    /**
     * The authenticated user's own reputation.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response([
            'user' => ['id' => $user->id, 'name' => $user->name],
            'reputation' => $this->reputation->forUser($user->id),
        ]);
    }
}

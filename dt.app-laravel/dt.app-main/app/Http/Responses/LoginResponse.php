<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $target = match ($request->user()?->role?->name) {
            'FACTURATION' => route('facturation.dashboard'),
            'DIRECTION_GENERALE' => route('direction.dashboard'),
            'DIRECTION_FINANCIERE' => route('direction.financiere'),
            'DIRECTION_EXPLOITATION' => route('direction.exploitation'),
            'PLANIFICATION' => route('planification.dashboard'),
            default => route('dashboard'),
        };

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'redirect' => $target])
            : redirect()->intended($target);
    }
}

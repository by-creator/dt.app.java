<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $target = url('/login');

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'redirect' => $target])
            : redirect()->to($target);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        return view('menu.index', [
            'menuSection' => null,
        ]);
    }

    public function section(Request $request, string $section): View
    {
        abort_unless($request->user()?->role?->name === 'ADMIN', 403);
        abort_unless(in_array($section, [
            'direction-generale',
            'direction-financiere',
            'direction-exploitation',
            'facturation',
            'planification',
        ], true), 404);

        return view('menu.index', [
            'menuSection' => $section,
        ]);
    }
}

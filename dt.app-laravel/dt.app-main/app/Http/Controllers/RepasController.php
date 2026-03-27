<?php

namespace App\Http\Controllers;

use App\Services\DematEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepasController extends Controller
{
    private const RECIPIENTS = [
        'assane.diouf@dakar-terminal.com',
        'clarisse.ngueabo@dakar-terminal.com',
        'philippe.napolitano@dakar-terminal.com',
        'jeannette.ndong@dakar-terminal.com',
        'aminata.ndiathe@dakar-terminal.com',
        'marc.bongoyeba@dakar-terminal.com',
        'moussa.thiaw@dakar-terminal.com',
        'cheikh.aw@dakar-terminal.com',
        'sophie-yande.diouf@dakar-terminal.com',
        'alioune-badara.dia@dakar-terminal.com',
        'fatou-kine.niang@dakar-terminal.com',
        'dieynaba.sy@dakar-terminal.com',
        'ramatoulaye.diallo@dakar-terminal.com',
        'christian.sarr@dakar-terminal.com',
        'mor-kebe.fall@dakar-terminal.com',
        'ousmane.tall@dakar-terminal.com',
        'fatou.konte@dakar-terminal.com',
        'aissatou.sow@dakar-terminal.com',
        'mame-aminata.ndaw@dakar-terminal.com',
        'mamadou-bafou.fall@dakar-terminal.com',
        'elhadji-babacar.sane@dakar-terminal.com',
        'mouhameth.mbengue@dakar-terminal.com',
        'ndeye-marieme.gueye@dakar-terminal.com',
        'aby.traore@dakar-terminal.com',
        'fatou.gueye@dakar-terminal.com',
        'rokhaya.cisse@dakar-terminal.com',
        'abdourahmane.diouf@dakar-terminal.com',
        'mohamed.ngom@dakar-terminal.com',
        'mamadou.diouf16@dakar-terminal.com',
        'aissata.ba@dakar-terminal.com',
        'basile.manga@dakar-terminal.com',
        'maimouna.fall@dakar-terminal.com',
        'ababacar.fall@dakar-terminal.com',
        'fatoumata-yaya.gueye@dakar-terminal.com',
        'charles.sarr@dakar-terminal.com',
        'aliounebadara.sy@dakar-terminal.com',
        'serigne.ndiaye@dakar-terminal.com',
        'marie.diop@dakar-terminal.com',
        'adama.n@dakar-terminal.com',
        'gaye.maliki@dakar-terminal.com'

        
    ];

    public function index(): View
    {
        return view('repas.index');
    }

    public function sendMenuDuJour(Request $request, DematEmailService $emailService): RedirectResponse
    {
        $validated = $request->validate([
            'plat1' => ['required', 'string', 'max:255'],
            'plat2' => ['required', 'string', 'max:255'],
        ]);

        try {
            $subject = '[Dakar Terminal] Menu du jour - '.now()->format('d/m/Y');
            $html = $emailService->buildMenuDuJourHtml($validated['plat1'], $validated['plat2']);

            foreach (self::RECIPIENTS as $recipient) {
                $emailService->sendMenuDuJourEmail($recipient, $subject, $html);
            }

            return back()->with('repasSuccess', 'Le menu du jour a ete envoye avec succes.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('repasError', 'Erreur lors de l\'envoi : '.$e->getMessage());
        }
    }
}

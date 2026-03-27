<?php

namespace App\Http\Controllers;

use App\Models\RattachementBl;
use App\Services\DematEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DematFormController extends Controller
{
    public function __construct(
        protected DematEmailService $dematEmailService,
    ) {}

    public function validationForm(): View
    {
        return view('demat.validation');
    }

    public function remiseForm(): View
    {
        return view('demat.remise');
    }

    public function submitValidation(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'numeroBl' => ['required', 'string', 'max:100'],
            'maisonTransit' => ['nullable', 'string', 'max:100'],
            'fileBl' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fileBadShipping' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fileDeclaration' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $submission = $this->attemptCreateSubmission($request, $validated, 'VALIDATION', 'EN_ATTENTE');

        $this->dematEmailService->sendValidationEmail(
            $submission?->nom ?? $validated['nom'],
            $submission?->prenom ?? $validated['prenom'],
            $submission?->email ?? $validated['email'],
            $submission?->bl ?? $validated['numeroBl'],
            $submission?->maison ?? ($validated['maisonTransit'] ?? null),
            $request->file('fileBl'),
            $request->file('fileBadShipping'),
            $request->file('fileDeclaration'),
        );

        return $this->successfulResponse($request, 'validation');
    }

    public function submitRemise(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'numeroBl' => ['required', 'string', 'max:100'],
            'maisonTransit' => ['required', 'string', 'max:100'],
            'fileDemandeManuscrite' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fileBadShipping' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fileBl' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fileFacture' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fileDeclaration' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $submission = $this->attemptCreateSubmission($request, $validated, 'REMISE', 'EN_ATTENTE_VALIDATION_FACTURATION');

        $this->dematEmailService->sendRemiseEmail(
            $submission?->nom ?? $validated['nom'],
            $submission?->prenom ?? $validated['prenom'],
            $submission?->email ?? $validated['email'],
            $submission?->bl ?? $validated['numeroBl'],
            $submission?->maison ?? ($validated['maisonTransit'] ?? null),
            $request->file('fileDemandeManuscrite'),
            $request->file('fileBadShipping'),
            $request->file('fileBl'),
            $request->file('fileFacture'),
            $request->file('fileDeclaration'),
        );

        return $this->successfulResponse($request, 'remise');
    }

    protected function createSubmission(Request $request, array $validated, string $type, string $statut): RattachementBl
    {
        return RattachementBl::query()->create([
            'user_id' => $request->user()?->id,
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'bl' => $validated['numeroBl'],
            'maison' => $validated['maisonTransit'] ?? null,
            'statut' => $statut,
            'type' => $type,
            'time_elapsed' => null,
        ]);
    }

    protected function attemptCreateSubmission(Request $request, array $validated, string $type, string $statut): ?RattachementBl
    {
        try {
            return $this->createSubmission($request, $validated, $type, $statut);
        } catch (\Throwable $exception) {
            Log::error('Demat submission persistence failed.', [
                'type' => $type,
                'email' => $validated['email'] ?? null,
                'numero_bl' => $validated['numeroBl'] ?? null,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function successfulResponse(Request $request, string $type): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'type' => $type,
            ]);
        }

        return redirect("/demat/{$type}?success=true");
    }
}

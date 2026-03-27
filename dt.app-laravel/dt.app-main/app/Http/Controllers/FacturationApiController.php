<?php

namespace App\Http\Controllers;

use App\Models\RattachementBl;
use App\Services\DematEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FacturationApiController extends Controller
{
    public function __construct(
        protected DematEmailService $dematEmailService,
    ) {}

    public function listRattachements(Request $request): JsonResponse
    {
        $this->authorizeFacturationAccess($request);

        $items = RattachementBl::query()
            ->where('type', 'VALIDATION')
            ->when(
                filled($request->string('statut')->toString()),
                fn ($builder) => $builder->where('statut', $request->string('statut')->toString())
            )
            ->latest()
            ->get();

        return response()->json($items->map(fn (RattachementBl $item) => $this->toArray($item)));
    }

    public function validateRattachement(Request $request, RattachementBl $rattachement): JsonResponse
    {
        $this->authorizeFacturationAccess($request);
        abort_unless($rattachement->type === 'VALIDATION', 404);

        $rattachement->forceFill([
            'statut' => 'VALIDE',
            'motif_rejet' => null,
        ])->save();

        if (filled($rattachement->email)) {
            $this->dematEmailService->sendValidationApprovedEmail(
                $rattachement->email,
                $rattachement->nom,
                $rattachement->prenom,
                $rattachement->bl,
            );
        }

        return response()->json($this->toArray($rattachement));
    }

    public function rejectRattachement(Request $request, RattachementBl $rattachement): JsonResponse
    {
        $this->authorizeFacturationAccess($request);
        abort_unless($rattachement->type === 'VALIDATION', 404);

        $validated = $request->validate([
            'motif' => ['required', 'string', 'max:100'],
        ]);

        $rattachement->forceFill([
            'statut' => 'REJETE',
            'motif_rejet' => $validated['motif'],
        ])->save();

        if (filled($rattachement->email)) {
            $this->dematEmailService->sendValidationRejectedEmail(
                $rattachement->email,
                $rattachement->nom,
                $rattachement->prenom,
                $rattachement->bl,
                $validated['motif'],
            );
        }

        return response()->json($this->toArray($rattachement));
    }

    public function listRemises(Request $request): JsonResponse
    {
        $this->authorizeFacturationAccess($request);

        $items = RattachementBl::query()
            ->where('type', 'REMISE')
            ->when(
                filled($request->string('statut')->toString()),
                fn ($builder) => $builder->where('statut', $request->string('statut')->toString())
            )
            ->latest()
            ->get();

        $items = $this->filterRemisesForRole($request, $items);

        return response()->json($items->values()->map(fn (RattachementBl $item) => $this->toArray($item)));
    }

    public function validateRemise(Request $request, RattachementBl $rattachement): JsonResponse
    {
        $this->authorizeFacturationAccess($request);
        abort_unless($rattachement->type === 'REMISE', 404);

        $roleName = $request->user()?->role?->name;
        $validated = $request->validate([
            'pourcentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'motif' => ['nullable', 'string', 'max:100'],
        ]);

        if (in_array($roleName, ['DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION', 'ADMIN', 'SUPER_U'], true) && $rattachement->statut === 'EN_ATTENTE_VALIDATION_DIRECTION') {
            if (! array_key_exists('pourcentage', $validated) || $validated['pourcentage'] === null || $validated['pourcentage'] === '') {
                throw ValidationException::withMessages([
                    'pourcentage' => 'Le pourcentage est obligatoire pour la validation finale.',
                ]);
            }

            $rattachement->forceFill([
                'statut' => 'VALIDE',
                'pourcentage' => $validated['pourcentage'],
                'motif_rejet' => null,
            ])->save();

            if (filled($rattachement->email)) {
                $this->dematEmailService->sendRemiseValidatedByDirectionEmailWithMotif(
                    $rattachement->email,
                    $rattachement->nom,
                    $rattachement->prenom,
                    $rattachement->bl,
                    $rattachement->pourcentage,
                    $validated['motif'] ?? null,
                );
            }

            return response()->json($this->toArray($rattachement));
        }

        abort_unless(in_array($roleName, ['FACTURATION', 'ADMIN', 'SUPER_U'], true), 403);
        abort_unless($rattachement->statut === 'EN_ATTENTE_VALIDATION_FACTURATION', 422);

        $rattachement->forceFill([
            'statut' => 'EN_ATTENTE_VALIDATION_DIRECTION',
            'motif_rejet' => null,
        ])->save();

        $this->dematEmailService->sendRemiseDirectionNotifEmail(
            $this->dematEmailService->directorEmail(),
            $rattachement->nom,
            $rattachement->prenom,
            $rattachement->bl,
            $rattachement->maison,
        );

        return response()->json($this->toArray($rattachement));
    }

    public function rejectRemise(Request $request, RattachementBl $rattachement): JsonResponse
    {
        $this->authorizeFacturationAccess($request);
        abort_unless($rattachement->type === 'REMISE', 404);

        $validated = $request->validate([
            'motif' => ['required', 'string', 'max:100'],
        ]);

        abort_unless(
            in_array($request->user()?->role?->name, ['FACTURATION', 'DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION', 'ADMIN', 'SUPER_U'], true),
            403
        );

        $rattachement->forceFill([
            'statut' => 'REJETE',
            'motif_rejet' => $validated['motif'],
        ])->save();

        if (filled($rattachement->email)) {
            $this->dematEmailService->sendRemiseRejectedEmail(
                $rattachement->email,
                $rattachement->nom,
                $rattachement->prenom,
                $rattachement->bl,
                $validated['motif'],
            );
        }

        return response()->json($this->toArray($rattachement));
    }

    private function filterRemisesForRole(Request $request, Collection $items): Collection
    {
        $roleName = $request->user()?->role?->name;

        if ($roleName === 'FACTURATION') {
            return $items->where('statut', 'EN_ATTENTE_VALIDATION_FACTURATION');
        }

        if (in_array($roleName, ['DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION'], true)) {
            return $items->where('statut', 'EN_ATTENTE_VALIDATION_DIRECTION');
        }

        return $items;
    }

    private function authorizeFacturationAccess(Request $request): void
    {
        abort_unless(
            in_array($request->user()?->role?->name, ['FACTURATION', 'DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION', 'ADMIN', 'SUPER_U'], true),
            403
        );
    }

    private function toArray(RattachementBl $item): array
    {
        return [
            'id' => $item->id,
            'nom' => $item->nom,
            'prenom' => $item->prenom,
            'email' => $item->email,
            'bl' => $item->bl,
            'maisonTransit' => $item->maison,
            'type' => $item->type,
            'statut' => $item->statut,
            'motifRejet' => $item->motif_rejet,
            'pourcentage' => $item->pourcentage,
            'createdAt' => $item->created_at?->toIso8601String(),
            'updatedAt' => $item->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class UnifyPrintController extends Controller
{
    private const LABELS = [
        'dateActivite' => 'Date',
        'typePersonne' => 'Type de personne',
        'compteIpaki' => 'Compte Ipaki',
        'compteNeptune' => 'Compte Neptune',
        'raisonSociale' => 'Raison sociale',
        'telephone' => 'Telephone',
        'email' => 'Email',
        'adresse' => 'Adresse',
        'dg' => 'Directeur General',
        'telDg' => 'Telephone DG',
        'df' => 'Directeur Financier',
        'telDf' => 'Telephone DF',
        'ninea' => 'NINEA',
        'registre' => 'Registre de commerce',
    ];

    public function printFiche(Request $request): View
    {
        return view('facturation.unify-print-template', [
            'data' => $this->buildPrintableData($request->all()),
            'type' => "FICHE D'OUVERTURE UNIFY",
            'isAttestation' => false,
            'dateActiviteFormatted' => $this->formatDateFr($request->string('dateActivite')->toString()),
        ]);
    }

    public function printAttestation(Request $request): View
    {
        $selected = collect($request->all())
            ->only(['compteIpaki', 'raisonSociale', 'ninea', 'registre'])
            ->all();

        return view('facturation.unify-print-template', [
            'data' => $this->buildPrintableData($selected),
            'type' => 'ATTESTATION UNIFY',
            'isAttestation' => true,
            'dateActiviteFormatted' => $this->formatDateFr($request->string('dateActivite')->toString()),
        ]);
    }

    private function buildPrintableData(array $data): array
    {
        return collect($data)
            ->reject(fn ($value, $key) => in_array($key, ['_token'], true))
            ->mapWithKeys(fn ($value, $key) => [self::LABELS[$key] ?? $key => $value])
            ->filter(fn ($value) => filled($value))
            ->all();
    }

    private function formatDateFr(?string $dateIso): string
    {
        if (! $dateIso) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($dateIso)->format('d/m/Y');
        } catch (\Throwable) {
            return $dateIso;
        }
    }
}

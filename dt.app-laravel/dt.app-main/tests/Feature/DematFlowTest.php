<?php

namespace Tests\Feature;

use App\Models\RattachementBl;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DematFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_submission_creates_a_pending_record(): void
    {
        Mail::fake();

        $response = $this->post('/demat/validation', [
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa@example.com',
            'numeroBl' => 'BL-001',
            'maisonTransit' => 'Transit SA',
            'fileBl' => UploadedFile::fake()->create('bl.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJson([
            'success' => true,
            'type' => 'validation',
        ]);

        $this->assertDatabaseHas('rattachement_bls', [
            'type' => 'VALIDATION',
            'statut' => 'EN_ATTENTE',
            'bl' => 'BL-001',
            'email' => 'awa@example.com',
        ]);
    }

    public function test_facturation_can_forward_remise_to_direction(): void
    {
        Mail::fake();

        $role = Role::query()->create(['name' => 'FACTURATION']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $remise = RattachementBl::query()->create([
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa@example.com',
            'bl' => 'BL-100',
            'maison' => 'Transit SA',
            'type' => 'REMISE',
            'statut' => 'EN_ATTENTE_VALIDATION_FACTURATION',
        ]);

        $response = $this->actingAs($user)->patch("/facturation/api/remises/{$remise->id}/valider", [], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('rattachement_bls', [
            'id' => $remise->id,
            'statut' => 'EN_ATTENTE_VALIDATION_DIRECTION',
        ]);
    }

    public function test_direction_can_validate_remise_with_percentage(): void
    {
        Mail::fake();

        $role = Role::query()->create(['name' => 'DIRECTION_GENERALE']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $remise = RattachementBl::query()->create([
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa@example.com',
            'bl' => 'BL-200',
            'maison' => 'Transit SA',
            'type' => 'REMISE',
            'statut' => 'EN_ATTENTE_VALIDATION_DIRECTION',
        ]);

        $response = $this->actingAs($user)->patch("/facturation/api/remises/{$remise->id}/valider", [
            'pourcentage' => 15,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('rattachement_bls', [
            'id' => $remise->id,
            'statut' => 'VALIDE',
        ]);
    }
}

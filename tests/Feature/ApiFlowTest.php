<?php

namespace Tests\Feature;

use App\Support\PayloadCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** One end-to-end check: login (encrypted payload) -> add patient -> visit -> count. */
class ApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow(): void
    {
        $this->seed();

        $this->getJson('/api/patients')->assertStatus(401);

        $this->postJson('/api/login', ['user_name' => 'admin', 'password' => 'password'])->assertStatus(422);

        $token = $this->postJson('/api/login', [
            'user_name' => PayloadCrypto::encrypt('admin'),
            'password' => PayloadCrypto::encrypt('password'),
        ])->assertOk()->json('data.access_token');

        $h = ['Authorization' => "Bearer $token"];

        $patient = ['name' => 'NURFAZA M.', 'nik' => '3214124123124120', 'address' => 'Jl Wisata 2 No 18'];
        $this->postJson('/api/patients', $patient, $h)->assertCreated()->assertJsonPath('data.no_rm', 'RM0001');
        $this->postJson('/api/patients', $patient, $h)->assertStatus(422)->assertJsonValidationErrors('nik');
        $this->postJson('/api/patients', ['name' => 'B', 'nik' => '1', 'address' => ''], $h)->assertStatus(422);

        $this->postJson('/api/visits', ['no_rm' => 'rm0001'], $h)->assertCreated();
        $this->postJson('/api/visits', ['no_rm' => 'RM0001'], $h)->assertCreated()->assertJsonPath('data.patient.visits_count', 2);
        $this->postJson('/api/visits', ['no_rm' => 'RM9999'], $h)->assertStatus(422);

        $this->getJson('/api/patients/RM0001', $h)->assertOk()->assertJsonPath('data.visits_count', 2);
        $this->getJson('/api/patients/RM9999', $h)->assertNotFound();

        // encrypted at rest
        $this->assertStringStartsWith('eyJ', \DB::table('patients')->value('nik'));
        $this->assertStringStartsWith('eyJ', \DB::table('users')->value('email'));
    }
}

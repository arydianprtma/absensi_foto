<?php

use App\Models\Student;
use App\Models\User;
use App\Services\InsightFaceService;

test('can access public webcam attendance page', function () {
    $response = $this->get('/absensi');

    $response->assertStatus(200);
});

test('can register student with face embedding', function () {
    $user = User::factory()->create();

    // Mock InsightFace service
    $this->mock(InsightFaceService::class, function ($mock) {
        $mock->shouldReceive('extractEmbedding')
            ->once()
            ->andReturn([
                'success' => true,
                'embedding' => array_fill(0, 512, 0.1),
                'bbox' => [10, 10, 100, 100],
            ]);
    });

    $response = $this->actingAs($user)->post('/students', [
        'nisn' => '1234567890',
        'name' => 'Budi Santoso',
        'class_name' => 'XII-RPL-1',
        'photo_base64' => 'data:image/jpeg;base64,'.base64_encode('fake-image-bytes'),
    ]);

    $response->assertRedirect('/students');
    $this->assertDatabaseHas('students', [
        'nisn' => '1234567890',
        'name' => 'Budi Santoso',
    ]);
});

test('student face verification records attendance successfully', function () {
    $student = Student::create([
        'nisn' => '9998887776',
        'name' => 'Siti Aminah',
        'class_name' => 'XII-RPL-2',
        'face_embedding' => array_fill(0, 512, 0.2),
    ]);

    $this->mock(InsightFaceService::class, function ($mock) {
        $mock->shouldReceive('verifyFace')
            ->once()
            ->andReturn([
                'matched' => true,
                'similarity' => 0.85,
                'message' => 'Wajah terverifikasi cocok!',
            ]);
    });

    $response = $this->postJson('/absensi/verifikasi', [
        'student_id' => $student->id,
        'image' => 'data:image/jpeg;base64,'.base64_encode('fake-snapshot-bytes'),
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'matched' => true,
        ]);

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
    ]);
});

test('automatic face identification records attendance without selecting student', function () {
    $student = Student::create([
        'nisn' => '8887776665',
        'name' => 'Randi Kurniawan',
        'class_name' => 'XII-TKJ-1',
        'face_embedding' => array_fill(0, 512, 0.3),
    ]);

    $this->mock(InsightFaceService::class, function ($mock) use ($student) {
        $mock->shouldReceive('identifyFace')
            ->once()
            ->andReturn([
                'matched' => true,
                'student_id' => $student->id,
                'similarity' => 0.92,
                'message' => 'Wajah mengenali siswa Randi Kurniawan',
            ]);
    });

    $response = $this->postJson('/absensi/verifikasi-otomatis', [
        'image' => 'data:image/jpeg;base64,'.base64_encode('fake-snapshot-bytes'),
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'matched' => true,
        ]);

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
    ]);
});

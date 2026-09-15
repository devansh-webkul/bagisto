<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\postJson;

it('should store an image uploaded through the rich text editor and return where it lives', function () {
    Storage::fake();

    $this->loginAsAdmin();

    $response = postJson(route('admin.tinymce.upload'), [
        'file' => UploadedFile::fake()->image('banner.png', 20, 20),
    ])->assertOk();

    expect($response->json('location'))->toContain('tinymce/');

    expect(Storage::allFiles('tinymce'))->toHaveCount(1);
});

it('should answer the rich text editor with the reason an upload was refused', function () {
    Storage::fake();

    $this->loginAsAdmin();

    postJson(route('admin.tinymce.upload'), [
        'file' => UploadedFile::fake()->create('payload.php', 1, 'text/x-php'),
    ])
        ->assertBadRequest()
        ->assertJsonStructure(['error']);

    expect(Storage::allFiles('tinymce'))->toBeEmpty();
});

it('should tell the rich text editor when no file was uploaded', function () {
    $this->loginAsAdmin();

    postJson(route('admin.tinymce.upload'))
        ->assertBadRequest()
        ->assertJsonPath('error', trans('admin::app.components.tinymce.errors.no-file-uploaded'));
});

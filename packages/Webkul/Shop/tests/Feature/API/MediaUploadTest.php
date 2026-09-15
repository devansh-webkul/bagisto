<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\ProductReviewAttachment;

use function Pest\Laravel\postJson;

// ============================================================================
// Review Attachments
// ============================================================================

it('should store the images and videos attached to a review', function () {
    Storage::fake();

    $product = $this->createSimpleProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.products.reviews.store', $product->id), [
        'title' => fake()->sentence(),
        'comment' => fake()->paragraph(),
        'rating' => 5,
        'attachments' => [
            UploadedFile::fake()->image('unboxing.jpg'),
            UploadedFile::fake()->create('unboxing.mp4', 64, 'video/mp4'),
        ],
    ])->assertOk();

    $attachments = ProductReviewAttachment::query()
        ->whereHas('review', fn ($query) => $query->where('product_id', $product->id))
        ->pluck('type')
        ->sort()
        ->values()
        ->all();

    expect($attachments)->toBe(['image', 'video']);
});

it('should refuse a review attachment that is neither an allowed image nor an allowed video', function (string $name, string $mimeType) {
    Storage::fake();

    $product = $this->createSimpleProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.products.reviews.store', $product->id), [
        'title' => fake()->sentence(),
        'comment' => fake()->paragraph(),
        'rating' => 5,
        'attachments' => [UploadedFile::fake()->create($name, 1, $mimeType)],
    ])
        ->assertJsonValidationErrorFor('attachments.0')
        ->assertUnprocessable();

    expect(Storage::allFiles())->toBeEmpty();
})->with([
    'svg' => ['drawing.svg', 'image/svg+xml'],
    'html' => ['page.html', 'text/html'],
    'avi' => ['clip.avi', 'video/x-msvideo'],
]);

// ============================================================================
// Image Search
// ============================================================================

it('should refuse a search image larger than the image search allows', function () {
    Storage::fake();

    postJson(route('shop.search.upload'), [
        'image' => UploadedFile::fake()->image('shoe.png')->size(2049),
    ])
        ->assertJsonValidationErrorFor('image')
        ->assertUnprocessable();

    expect(Storage::allFiles('product-search'))->toBeEmpty();
});

it('should store a search image of an allowed type', function () {
    Storage::fake();

    postJson(route('shop.search.upload'), [
        'image' => UploadedFile::fake()->image('shoe.gif'),
    ])->assertOk();

    expect(Storage::allFiles('product-search'))->toHaveCount(1);
});

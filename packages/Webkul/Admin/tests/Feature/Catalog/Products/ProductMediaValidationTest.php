<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Helpers\CacheGeneration;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\Product;

use function Pest\Laravel\putJson;

/**
 * The fields a simple product update needs to pass validation, with the given ones added.
 */
function productMediaUpdatePayload(Product $product, array $data = []): array
{
    return array_merge([
        'sku' => $product->sku,
        'url_key' => $product->url_key,
        'short_description' => fake()->sentence(),
        'description' => fake()->paragraph(),
        'name' => fake()->words(3, true),
        'price' => fake()->randomFloat(2, 1, 1000),
        'weight' => fake()->numberBetween(0, 100),
        'channel' => core()->getDefaultChannelCode(),
        'locale' => app()->getLocale(),
    ], $data);
}

/**
 * Add a new image type attribute to the family of the given product.
 */
function addImageAttributeToFamily(Product $product): Attribute
{
    $attribute = Attribute::factory()->create([
        'code' => 'media_'.strtolower(fake()->unique()->lexify('??????')),
        'type' => 'image',
        'is_required' => false,
        'value_per_locale' => false,
        'value_per_channel' => false,
    ]);

    $product->attribute_family->attribute_groups()->first()->custom_attributes()->attach($attribute->id, ['position' => 999]);

    return $attribute;
}

// ============================================================================
// Gallery
// ============================================================================

it('should refuse a gallery image of a type the catalog does not allow', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    $this->loginAsAdmin();

    putJson(route('admin.catalog.products.update', $product->id), productMediaUpdatePayload($product, [
        'images' => ['files' => ['image_0' => UploadedFile::fake()->image('animation.gif')]],
    ]))
        ->assertJsonValidationErrorFor('images.files.image_0')
        ->assertUnprocessable();
});

it('should refuse a gallery video larger than the configured file upload size', function () {
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.products.attribute.file_attribute_upload_size'],
        ['value' => '100']
    );

    CacheGeneration::bump(CoreConfigRepository::class);

    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    $this->loginAsAdmin();

    putJson(route('admin.catalog.products.update', $product->id), productMediaUpdatePayload($product, [
        'videos' => ['files' => ['video_0' => UploadedFile::fake()->create('clip.mp4', 150, 'video/mp4')]],
    ]))
        ->assertJsonValidationErrorFor('videos.files.video_0')
        ->assertUnprocessable();
});

// ============================================================================
// Image Attributes
// ============================================================================

it('should refuse a file that is not an image as the value of an image attribute', function () {
    Storage::fake();

    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    $attribute = addImageAttributeToFamily($product);

    $this->loginAsAdmin();

    putJson(route('admin.catalog.products.update', $product->id), productMediaUpdatePayload($product, [
        $attribute->code => UploadedFile::fake()->create('payload.php', 1, 'text/x-php'),
    ]))
        ->assertJsonValidationErrorFor($attribute->code)
        ->assertUnprocessable();

    expect(Storage::allFiles('product/'.$product->id))->toBeEmpty();
});

it('should store an image uploaded as the value of an image attribute', function () {
    Storage::fake();

    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    $attribute = addImageAttributeToFamily($product);

    $this->loginAsAdmin();

    putJson(route('admin.catalog.products.update', $product->id), productMediaUpdatePayload($product, [
        $attribute->code => UploadedFile::fake()->image('label.png', 20, 20),
    ]))
        ->assertRedirect(route('admin.catalog.products.index'));

    expect(Storage::allFiles('product/'.$product->id))->toHaveCount(1);
});

it('should hand the product form the configured size and the types of the image attribute context', function () {
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.products.attribute.image_attribute_upload_size'],
        ['value' => '512']
    );

    CacheGeneration::bump(CoreConfigRepository::class);

    $attribute = Attribute::factory()->make(['type' => 'image', 'is_required' => false, 'validation' => '']);

    expect($attribute->validations)
        ->toContain('size:512')
        ->toContain('"image/webp"')
        ->not->toContain('image/gif');
});

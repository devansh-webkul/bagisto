<?php

use Illuminate\Http\UploadedFile;
use Webkul\Core\Helpers\CacheGeneration;
use Webkul\Core\Helpers\MediaFileName;
use Webkul\Core\Helpers\MediaUpload;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;

/**
 * Whether an upload passes the rule of the given media context.
 */
function passesMediaUpload(string $context, UploadedFile $file, ?MediaUpload $mediaUpload = null): bool
{
    $mediaUpload ??= app(MediaUpload::class);

    return validator(['file' => $file], ['file' => [$mediaUpload->rule($context)]])->passes();
}

/**
 * Build a real upload, whose type is detected from its contents rather than from its name.
 */
function mediaUploadNamed(string $name, string $contents): UploadedFile
{
    static $handles = [];

    $handles[] = $handle = tmpfile();

    fwrite($handle, $contents);

    return new UploadedFile(stream_get_meta_data($handle)['uri'], $name, null, null, true);
}

/**
 * Store a core configuration value, scoped to the current channel when the field is channel based, and
 * leave behind any read the configuration repository cached before it.
 */
function storeMediaCoreConfig(string $code, string $value, bool $channelBased = false): void
{
    CoreConfig::query()->updateOrCreate([
        'code' => $code,
        'channel_code' => $channelBased ? core()->getRequestedChannelCode() : null,
    ], [
        'value' => $value,
    ]);

    CacheGeneration::bump(CoreConfigRepository::class);
}

/**
 * A media upload whose contexts are the given ones instead of the core ones.
 */
function mediaUploadWithContexts(array $contexts): MediaUpload
{
    return new class($contexts) extends MediaUpload
    {
        /**
         * Create a media upload with its own contexts.
         */
        public function __construct(protected array $definitions) {}

        /**
         * Get the contexts given to the instance.
         */
        protected function contexts(): array
        {
            return $this->definitions;
        }
    };
}

// ============================================================================
// Images
// ============================================================================

it('should accept an image of an allowed type', function () {
    expect(passesMediaUpload(MediaUpload::IMAGE, UploadedFile::fake()->image('photo.png')))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::IMAGE, UploadedFile::fake()->image('photo.webp')))->toBeTrue();
});

it('should refuse an image type the context does not allow', function () {
    expect(passesMediaUpload(MediaUpload::IMAGE, UploadedFile::fake()->image('animation.gif')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::IMAGE, UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::SEARCH_IMAGE, UploadedFile::fake()->image('animation.gif')))->toBeTrue();
});

it('should refuse a file whose contents are not an image, whatever its name', function () {
    expect(passesMediaUpload(MediaUpload::IMAGE, mediaUploadNamed('avatar.png', '<?php echo "owned";')))->toBeFalse();
});

it('should refuse an image whose name carries an extension the context does not allow', function () {
    $png = UploadedFile::fake()->image('photo.png');

    $renamed = new UploadedFile($png->getRealPath(), 'photo.txt', null, null, true);

    expect(passesMediaUpload(MediaUpload::IMAGE, $renamed))->toBeFalse();
});

it('should accept an svg only where the context admits one', function () {
    $svg = UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml');

    expect(passesMediaUpload(MediaUpload::EDITOR_IMAGE, $svg))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::BRANDING, $svg))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::REVIEW_ATTACHMENT, $svg))->toBeFalse();
});

it('should accept an icon only where the context admits one', function () {
    $icon = UploadedFile::fake()->create('favicon.ico', 1, 'image/vnd.microsoft.icon');

    expect(passesMediaUpload(MediaUpload::FAVICON, $icon))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::IMAGE, $icon))->toBeFalse();
});

it('should refuse an image larger than the configured size', function () {
    storeMediaCoreConfig('catalog.products.attribute.image_attribute_upload_size', '100');

    expect(passesMediaUpload(MediaUpload::IMAGE_ATTRIBUTE, UploadedFile::fake()->image('swatch.png')->size(150)))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::IMAGE_ATTRIBUTE, UploadedFile::fake()->image('swatch.png')->size(50)))->toBeTrue();
});

it('should refuse an image whose dimensions fall outside the ones the context sets', function () {
    $mediaUpload = mediaUploadWithContexts([
        'thumbnail' => [
            'extensions' => MediaUpload::IMAGE_EXTENSIONS,
            'dimensions' => ['max_width' => 100],
        ],
    ]);

    expect(passesMediaUpload('thumbnail', UploadedFile::fake()->image('wide.png', 200, 50), $mediaUpload))->toBeFalse()
        ->and(passesMediaUpload('thumbnail', UploadedFile::fake()->image('narrow.png', 80, 50), $mediaUpload))->toBeTrue();
});

// ============================================================================
// Videos
// ============================================================================

it('should accept a video of an allowed type', function () {
    expect(passesMediaUpload(MediaUpload::VIDEO, UploadedFile::fake()->create('clip.mp4', 64, 'video/mp4')))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::VIDEO, UploadedFile::fake()->create('clip.webm', 64, 'video/webm')))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::VIDEO, UploadedFile::fake()->create('clip.mov', 64, 'video/quicktime')))->toBeTrue();
});

it('should refuse a video type the context does not allow', function () {
    expect(passesMediaUpload(MediaUpload::VIDEO, UploadedFile::fake()->create('clip.avi', 64, 'video/x-msvideo')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::VIDEO, UploadedFile::fake()->create('song.ogg', 64, 'audio/ogg')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::VIDEO, UploadedFile::fake()->image('photo.png')))->toBeFalse();
});

it('should refuse a video larger than the configured size', function () {
    storeMediaCoreConfig('catalog.products.attribute.file_attribute_upload_size', '100');

    expect(passesMediaUpload(MediaUpload::PRODUCT_VIDEO, UploadedFile::fake()->create('clip.mp4', 150, 'video/mp4')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::PRODUCT_VIDEO, UploadedFile::fake()->create('clip.mp4', 50, 'video/mp4')))->toBeTrue();
});

it('should hold theme section media to its own size limit', function () {
    expect(app(MediaUpload::class)->maxSize(MediaUpload::SECTION_MEDIA))->toBe(51200)
        ->and(passesMediaUpload(MediaUpload::SECTION_MEDIA, UploadedFile::fake()->create('hero.mp4', 51201, 'video/mp4')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::SECTION_MEDIA, UploadedFile::fake()->create('hero.mp4', 51200, 'video/mp4')))->toBeTrue();
});

it('should accept a product video of an unrecognised format only when its name carries a video extension', function () {
    expect(passesMediaUpload(MediaUpload::PRODUCT_VIDEO, UploadedFile::fake()->create('clip.mov', 64, 'application/octet-stream')))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::PRODUCT_VIDEO, UploadedFile::fake()->create('payload.exe', 64, 'application/octet-stream')))->toBeFalse();
});

it('should refuse an unrecognised format where the context does not tolerate one', function () {
    expect(passesMediaUpload(MediaUpload::SECTION_MEDIA, UploadedFile::fake()->create('clip.mp4', 64, 'application/octet-stream')))->toBeFalse()
        ->and(passesMediaUpload(MediaUpload::REVIEW_ATTACHMENT, UploadedFile::fake()->create('clip.mp4', 64, 'application/octet-stream')))->toBeFalse();
});

// ============================================================================
// Configuration
// ============================================================================

it('should take the size from the core configuration over the default', function () {
    storeMediaCoreConfig('catalog.products.attribute.image_attribute_upload_size', '4096');

    expect(app(MediaUpload::class)->maxSize(MediaUpload::IMAGE_ATTRIBUTE))->toBe(4096);
});

it('should fall back to the default size when the configured one is empty or not positive', function () {
    storeMediaCoreConfig('catalog.products.attribute.image_attribute_upload_size', '');

    expect(app(MediaUpload::class)->maxSize(MediaUpload::IMAGE_ATTRIBUTE))->toBe(2048);

    storeMediaCoreConfig('catalog.products.attribute.image_attribute_upload_size', '0');

    expect(app(MediaUpload::class)->maxSize(MediaUpload::IMAGE_ATTRIBUTE))->toBe(2048);
});

it('should fall back to the defaults when no configuration exists for the context', function () {
    $mediaUpload = mediaUploadWithContexts([
        'banner' => [
            'extensions' => ['png'],
            'extensions_config' => 'testing.media.absent_types',
            'max_size' => 300,
            'max_size_config' => 'testing.media.absent_size',
        ],
        'unlimited' => [
            'extensions' => ['png'],
        ],
    ]);

    expect($mediaUpload->maxSize('banner'))->toBe(300)
        ->and($mediaUpload->extensions('banner'))->toBe(['png'])
        ->and($mediaUpload->maxSize('unlimited'))->toBeNull()
        ->and(passesMediaUpload('unlimited', UploadedFile::fake()->image('big.png')->size(90000), $mediaUpload))->toBeTrue();
});

it('should take the allowed types from the core configuration over the defaults', function () {
    storeMediaCoreConfig('sales.rma.setting.allowed_file_extension', 'image/png', channelBased: true);

    expect(app(MediaUpload::class)->extensions(MediaUpload::RMA_ATTACHMENT))->toBe(['png'])
        ->and(passesMediaUpload(MediaUpload::RMA_ATTACHMENT, UploadedFile::fake()->image('receipt.png')))->toBeTrue()
        ->and(passesMediaUpload(MediaUpload::RMA_ATTACHMENT, UploadedFile::fake()->image('receipt.jpg')))->toBeFalse();
});

it('should never let the core configuration enable a type that runs or renders as a page', function () {
    storeMediaCoreConfig('sales.rma.setting.allowed_file_extension', 'image/png,text/html,image/svg+xml,application/x-httpd-php', channelBased: true);

    expect(app(MediaUpload::class)->extensions(MediaUpload::RMA_ATTACHMENT))->toBe(['png']);

    storeMediaCoreConfig('sales.rma.setting.allowed_file_extension', 'text/html,svg,php', channelBased: true);

    expect(app(MediaUpload::class)->extensions(MediaUpload::RMA_ATTACHMENT))->toBe(['jpeg', 'jpg', 'png', 'webp'])
        ->and(passesMediaUpload(MediaUpload::RMA_ATTACHMENT, UploadedFile::fake()->create('page.html', 1, 'text/html')))->toBeFalse();
});

it('should refuse a context that does not exist', function () {
    app(MediaUpload::class)->rule('unknown_context');
})->throws(InvalidArgumentException::class);

it('should store media only under an extension some context may accept, and never an svg', function () {
    expect(MediaFileName::ALLOWED_EXTENSIONS)
        ->toEqualCanonicalizing([...MediaUpload::SAFE_IMAGE_EXTENSIONS, ...MediaUpload::SAFE_VIDEO_EXTENSIONS])
        ->not->toContain('svg');
});

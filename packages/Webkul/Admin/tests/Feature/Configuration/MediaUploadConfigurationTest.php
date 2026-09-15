<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Helpers\MediaUpload;
use Webkul\Core\SystemConfig\ItemField;

use function Pest\Laravel\postJson;

// ============================================================================
// Configuration Fields
// ============================================================================

it('should refuse a configuration image that is not an image', function () {
    Storage::fake();

    $this->loginAsAdmin();

    postJson(route('admin.configuration.store', ['general', 'design']), [
        'locale' => app()->getLocale(),
        'channel' => core()->getDefaultChannelCode(),
        'general' => [
            'design' => [
                'admin_logo' => [
                    'logo_image' => UploadedFile::fake()->create('payload.php', 1, 'text/x-php'),
                ],
            ],
        ],
    ])
        ->assertJsonValidationErrorFor('general.design.admin_logo.logo_image')
        ->assertUnprocessable();

    expect(Storage::allFiles('configuration'))->toBeEmpty();
});

it('should accept an svg only for the configuration fields whose media context admits one', function () {
    Storage::fake();

    $this->loginAsAdmin();

    postJson(route('admin.configuration.store', ['general', 'design']), [
        'locale' => app()->getLocale(),
        'channel' => core()->getDefaultChannelCode(),
        'general' => [
            'design' => [
                'admin_logo' => [
                    'logo_image' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
                ],
            ],
        ],
    ])->assertRedirect();

    expect(Storage::allFiles('configuration'))->toHaveCount(1);

    postJson(route('admin.configuration.store', ['sales', 'invoice_settings']), [
        'locale' => app()->getLocale(),
        'channel' => core()->getDefaultChannelCode(),
        'sales' => [
            'invoice_settings' => [
                'pdf_print_outs' => [
                    'logo' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
                ],
            ],
        ],
    ])
        ->assertJsonValidationErrorFor('sales.invoice_settings.pdf_print_outs.logo')
        ->assertUnprocessable();
});

it('should hand the browser the extensions of the media context a configuration field names', function () {
    $field = system_config()->getConfigField('general.design.admin_logo.favicon');

    $itemField = new ItemField(
        item_key: 'general.design.admin_logo',
        name: $field['name'],
        title: $field['title'],
        info: null,
        type: $field['type'],
        path: null,
        validation: $field['validation'],
        depends: null,
        default: null,
        channel_based: false,
        locale_based: false,
        placeholder: null,
        options: [],
    );

    expect($field['validation'])->toBe('media:'.MediaUpload::BRANDING_FAVICON)
        ->and($itemField->getValidations())->toBe('mimes:bmp,jpeg,jpg,png,webp,svg,ico');
});

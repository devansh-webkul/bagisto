<?php

namespace Webkul\Core\Helpers;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use InvalidArgumentException;
use Symfony\Component\Mime\MimeTypes;

class MediaUpload
{
    /**
     * An image, wherever no more specific context applies.
     */
    public const IMAGE = 'image';

    /**
     * A video, wherever no more specific context applies.
     */
    public const VIDEO = 'video';

    /**
     * A favicon, which may also be an icon file.
     */
    public const FAVICON = 'favicon';

    /**
     * Branding artwork uploaded through the configuration, which may also be an SVG.
     */
    public const BRANDING = 'branding';

    /**
     * A favicon uploaded through the configuration, which may also be an SVG or an icon file.
     */
    public const BRANDING_FAVICON = 'branding_favicon';

    /**
     * A video in a product's gallery.
     */
    public const PRODUCT_VIDEO = 'product_video';

    /**
     * The value of an image type product attribute.
     */
    public const IMAGE_ATTRIBUTE = 'image_attribute';

    /**
     * An image or a video placed in a theme section.
     */
    public const SECTION_MEDIA = 'section_media';

    /**
     * An image or a video attached to a product review.
     */
    public const REVIEW_ATTACHMENT = 'review_attachment';

    /**
     * A file attached to a return request or to its conversation.
     */
    public const RMA_ATTACHMENT = 'rma_attachment';

    /**
     * An image a customer searches the catalog with, which is handed to the AI provider as is.
     */
    public const SEARCH_IMAGE = 'search_image';

    /**
     * An image inserted through the rich text editor, where an SVG is sanitized once stored.
     */
    public const EDITOR_IMAGE = 'editor_image';

    /**
     * The image extensions accepted by any context that does not name its own.
     */
    public const IMAGE_EXTENSIONS = ['bmp', 'jpeg', 'jpg', 'png', 'webp'];

    /**
     * The video extensions accepted by any context that does not name its own.
     */
    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'ogv', 'ogg'];

    /**
     * Image extensions never run or rendered as a page, the only ones a configured value may enable.
     */
    public const SAFE_IMAGE_EXTENSIONS = ['avif', 'bmp', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'webp'];

    /**
     * Video extensions never run or rendered as a page, the only ones a configured value may enable.
     */
    public const SAFE_VIDEO_EXTENSIONS = ['mov', 'mp4', 'ogg', 'ogv', 'webm'];

    /**
     * The content type reported for a file whose format the server cannot recognise.
     */
    public const OCTET_STREAM = 'application/octet-stream';

    /**
     * Get the validation rule for an upload in the given context.
     */
    public function rule(string $context): File
    {
        $rule = File::types($this->mimeTypes($context))
            ->extensions($this->extensions($context));

        if ($maxSize = $this->maxSize($context)) {
            $rule->max($maxSize);
        }

        if ($dimensions = $this->definition($context)['dimensions'] ?? null) {
            $rule->rules(Rule::dimensions($dimensions));
        }

        return $rule;
    }

    /**
     * Get the extensions an upload in the given context may carry, from the core configuration when it
     * enables any, otherwise from the context's defaults.
     */
    public function extensions(string $context): array
    {
        $definition = $this->definition($context);

        return $this->configuredExtensions($definition['extensions_config'] ?? null)
            ?: $definition['extensions'];
    }

    /**
     * Get the content types an upload in the given context may be detected as.
     */
    public function mimeTypes(string $context): array
    {
        $mimeTypes = collect($this->extensions($context))
            ->flatMap(fn ($extension) => $this->mimeTypesOf($extension));

        if (! empty($this->definition($context)['allow_octet_stream'])) {
            $mimeTypes->push(self::OCTET_STREAM);
        }

        return $mimeTypes->unique()->values()->all();
    }

    /**
     * Get the largest size in kilobytes an upload in the given context may have, from the core
     * configuration when it holds a positive number, otherwise from the context's default.
     */
    public function maxSize(string $context): ?int
    {
        $definition = $this->definition($context);

        $configured = isset($definition['max_size_config'])
            ? core()->getConfigData($definition['max_size_config'])
            : null;

        if (
            is_numeric($configured)
            && $configured > 0
        ) {
            return (int) $configured;
        }

        return $definition['max_size'] ?? null;
    }

    /**
     * Get every context by name: its default extensions and, optionally, a size in kilobytes, the core
     * configuration overriding either, image dimensions, and whether an unrecognised format passes.
     */
    protected function contexts(): array
    {
        return [
            self::IMAGE => [
                'extensions' => self::IMAGE_EXTENSIONS,
            ],

            self::VIDEO => [
                'extensions' => self::VIDEO_EXTENSIONS,
            ],

            self::FAVICON => [
                'extensions' => [...self::IMAGE_EXTENSIONS, 'ico'],
            ],

            self::BRANDING => [
                'extensions' => [...self::IMAGE_EXTENSIONS, 'svg'],
            ],

            self::BRANDING_FAVICON => [
                'extensions' => [...self::IMAGE_EXTENSIONS, 'svg', 'ico'],
            ],

            self::PRODUCT_VIDEO => [
                'extensions' => self::VIDEO_EXTENSIONS,
                'max_size' => 2048,
                'max_size_config' => 'catalog.products.attribute.file_attribute_upload_size',
                'allow_octet_stream' => true,
            ],

            self::IMAGE_ATTRIBUTE => [
                'extensions' => self::IMAGE_EXTENSIONS,
                'max_size' => 2048,
                'max_size_config' => 'catalog.products.attribute.image_attribute_upload_size',
            ],

            self::SECTION_MEDIA => [
                'extensions' => [...self::IMAGE_EXTENSIONS, ...self::VIDEO_EXTENSIONS],
                'max_size' => 51200,
            ],

            self::REVIEW_ATTACHMENT => [
                'extensions' => [...self::IMAGE_EXTENSIONS, ...self::VIDEO_EXTENSIONS],
            ],

            self::RMA_ATTACHMENT => [
                'extensions' => ['jpeg', 'jpg', 'png', 'webp'],
                'extensions_config' => 'sales.rma.setting.allowed_file_extension',
            ],

            self::SEARCH_IMAGE => [
                'extensions' => ['gif', 'jpeg', 'jpg', 'png', 'webp'],
                'max_size' => 2048,
            ],

            self::EDITOR_IMAGE => [
                'extensions' => ['gif', 'jpeg', 'jpg', 'png', 'webp', 'svg'],
            ],
        ];
    }

    /**
     * Get the definition of a context, refusing a name that none carries.
     */
    protected function definition(string $context): array
    {
        return $this->contexts()[$context]
            ?? throw new InvalidArgumentException("Unknown media upload context [{$context}].");
    }

    /**
     * Get the extensions a core configuration value enables, given as extensions or content types,
     * keeping only the ones never run or rendered as a page.
     */
    protected function configuredExtensions(?string $path): array
    {
        if (! $path) {
            return [];
        }

        $value = core()->getConfigData($path);

        return collect(is_array($value) ? $value : explode(',', (string) $value))
            ->map(fn ($type) => strtolower(trim((string) $type)))
            ->filter()
            ->flatMap(fn ($type) => str_contains($type, '/') ? MimeTypes::getDefault()->getExtensions($type) : [$type])
            ->intersect([...self::SAFE_IMAGE_EXTENSIONS, ...self::SAFE_VIDEO_EXTENSIONS])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get the content types of an extension, limited to the kind of media it is, since a name such as
     * ogg is shared with audio.
     */
    protected function mimeTypesOf(string $extension): array
    {
        $kind = in_array($extension, self::SAFE_VIDEO_EXTENSIONS) ? 'video/' : 'image/';

        return array_filter(
            MimeTypes::getDefault()->getMimeTypes($extension),
            fn ($mimeType) => str_starts_with($mimeType, $kind)
        );
    }
}

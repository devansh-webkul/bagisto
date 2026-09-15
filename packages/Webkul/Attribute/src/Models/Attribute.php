<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Database\Factories\AttributeFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Core\Helpers\MediaUpload;
use Webkul\Core\Rules\Regex;

class Attribute extends TranslatableModel implements AttributeContract
{
    use HasFactory;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = ['name'];

    /**
     * Attribute type fields.
     *
     * @var array
     */
    public $attributeTypeFields = [
        'text' => 'text_value',
        'textarea' => 'text_value',
        'price' => 'float_value',
        'boolean' => 'boolean_value',
        'select' => 'integer_value',
        'multiselect' => 'text_value',
        'datetime' => 'datetime_value',
        'date' => 'date_value',
        'file' => 'text_value',
        'image' => 'text_value',
        'checkbox' => 'text_value',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'admin_name',
        'type',
        'enable_wysiwyg',
        'position',
        'is_required',
        'is_unique',
        'validation',
        'regex',
        'value_per_locale',
        'value_per_channel',
        'default_value',
        'is_filterable',
        'is_configurable',
        'is_visible_on_front',
        'is_user_defined',
        'swatch_type',
        'is_comparable',
    ];

    /**
     * Get the options.
     */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOptionProxy::modelClass());
    }

    /**
     * Scope a query to the filterable attributes, leaving out image swatches.
     */
    public function scopeFilterableAttributes(Builder $query): Builder
    {
        return $query->where('is_filterable', 1)
            ->where('swatch_type', '<>', 'image')
            ->orderBy('position');
    }

    /**
     * Get the attribute value column that holds this attribute's type.
     *
     * @return string
     */
    protected function getColumnNameAttribute()
    {
        return $this->attributeTypeFields[$this->type];
    }

    /**
     * Get the attribute's validation rules as the product form's Vee Validate object.
     *
     * @return string
     */
    protected function getValidationsAttribute()
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required: true';
        }

        if ($this->type == 'price') {
            $validations[] = 'decimal: true';
        }

        if ($this->type == 'file') {
            $retVal = core()->getConfigData('catalog.products.attribute.file_attribute_upload_size') ?? '2048';

            if ($retVal) {
                $validations[] = 'size:'.$retVal;
            }
        }

        if ($this->type == 'image') {
            $mediaUpload = app(MediaUpload::class);

            $maxSize = $mediaUpload->maxSize(MediaUpload::IMAGE_ATTRIBUTE);

            $validations[] = $maxSize ? 'size:'.$maxSize : null;

            $validations[] = 'mimes: '.json_encode($mediaUpload->mimeTypes(MediaUpload::IMAGE_ATTRIBUTE), JSON_UNESCAPED_SLASHES);
        }

        if ($this->validation == 'regex') {
            if (Regex::isUsable($this->regex)) {
                $validations[] = 'regex: '.$this->regex;
            }
        } elseif ($this->validation) {
            $validations[] = $this->validation.': true';
        }

        $validations = '{ '.implode(', ', array_filter($validations)).' }';

        return $validations;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AttributeFactory::new();
    }
}

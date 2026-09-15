<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Helpers\MediaUpload;
use Webkul\Core\Traits\Sanitizer;

class TinyMCEController extends Controller
{
    use Sanitizer;

    /**
     * Storage folder path.
     *
     * @var string
     */
    private $storagePath = 'tinymce';

    /**
     * Upload file from tinymce.
     *
     * @return JsonResponse
     */
    public function upload()
    {
        $result = $this->storeMedia();

        if (isset($result['error'])) {
            return response()->json([
                'error' => $result['error'],
            ], 400);
        }

        if (! empty($result)) {
            return response()->json([
                'location' => $result['file_url'],
            ]);
        }

        return response()->json([
            'error' => trans('admin::app.components.tinymce.errors.file-upload-failed'),
        ], 400);
    }

    /**
     * Store the uploaded image, or describe why it was refused in the shape TinyMCE reads.
     *
     * @return array
     */
    public function storeMedia()
    {
        if (! request()->hasFile('file')) {
            return ['error' => trans('admin::app.components.tinymce.errors.no-file-uploaded')];
        }

        $validator = validator(request()->all(), [
            'file' => [app(MediaUpload::class)->rule(MediaUpload::EDITOR_IMAGE)],
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors()->first('file')];
        }

        $file = request()->file('file');

        $path = $file->store($this->storagePath);

        $this->sanitizeSVG($path, $file->getMimeType());

        return [
            'file' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_url' => Storage::url($path),
        ];
    }
}

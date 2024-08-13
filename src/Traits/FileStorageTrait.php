<?php

namespace CodingPartners\AutoController\Traits;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait FileStorageTrait
{
    /**
     *  Store photo and protect the site
     *
     * @param  string  $folderName The folder to upload the file to.
     * @param  file  $file The name of the file input field in the request.
     * @return string|null The url the photo url.
     */
    public function storeFile($file, string $folderName)
    {
        // $file = $request->file;
        $originalName = $file->getClientOriginalName();

        // Check for double extensions in the file name
        if (preg_match('/\.[^.]+\./', $originalName)) {
            throw new Exception(trans('general.notAllowedAction'), 403);
        }

        //validate the mime type and extentions
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpeg', 'png', 'gif', 'jpg'];
        $mime_type = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        //validate the mime type and extentions
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jfif'];
        $allowedExtensions = ['jpeg', 'png', 'gif', 'jpg', 'jfif'];
        $mime_type = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();

        if (!in_array($mime_type, $allowedMimeTypes) || !in_array($extension, $allowedExtensions)) {
            throw new Exception(trans('general.invalidFileType'), 403);
        }

        // Sanitize the file name to prevent path traversal
        $fileName = Str::random(32);
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '', $fileName);

        //store the file in the public disc
        $path = $file->storeAs($folderName, $fileName . '.' . $extension, 'public');

        //verify the path to ensure it matches the expected pattern
        $expectedPath = storage_path('app/public/' . $folderName . '/' . $fileName . '.' . $extension);
        $actualPath = storage_path('app/public/' . $path);
        if ($actualPath !== $expectedPath) {
            Storage::disk('public')->delete($path);
            throw new Exception(trans('general.notAllowedAction'), 403);
        }

        // get the url of the stored file
        // $url = Storage::disk('public')->url($path);
        $url = Storage::url($path);
        return $url;
    }


    /**
     * Check if a file exists and upload it.
     *
     * This method checks if a file exists in the request and uploads it to the specified folder.
     * If the file doesn't exist, it returns null.
     *
     * @param  Request  $request The HTTP request object.
     * @param  string  $folder The folder to upload the file to.
     * @param  string  $fileColumnName The name of the file input field in the request.
     * @return string|null The file path if the file exists, otherwise null.
     */
    public function fileExists($file, $old_file, string $folderName)
    {
        if (isset($file)) {
            return null;
        }
        $filePath = public_path($old_file);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        return $this->storeFile($file, $folderName);
    }
}

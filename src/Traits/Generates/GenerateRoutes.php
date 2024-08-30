<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait GenerateRoutes
{
    /**
     * Generate and add API routes for a given model.
     *
     * This method generates RESTful API routes for a specific model and adds them to the `routes/api.php` file.
     * The generated routes allow for managing the specified model through standard RESTful operations.
     * If `$softDeleteRoutes` is set to true, additional routes for handling soft deleted records
     * (trashed, restore, and force delete) will also be generated.
     *
     * @param string $model The name of the model for which the routes are being generated.
     * @param bool $softDeleteRoutes Whether to include routes for handling soft deleted records.
     *
     * @return void
     */
    protected function generateRoutes($model, $softDeleteRoutes)
    {
        $this->info("Generating routes/api.php for $model...");

        $models = Str::plural($model);
        $routesContent = "\n/**
 * {$model} Management Routes
 *
 * These routes handle {$model} management operations.
 */
Route::apiResource('{$models}', App\Http\Controllers\\{$model}Controller::class);";

        if ($softDeleteRoutes) {

            $routesContent .= "
Route::get('{$models}/trashed', [App\Http\Controllers\\{$model}Controller::class, 'trashed']);
Route::post('{$models}/{id}/restore', [App\Http\Controllers\\{$model}Controller::class, 'restore']);
Route::delete('{$models}/{id}/forceDelete', [App\Http\Controllers\\{$model}Controller::class, 'forceDelete']);";
        }

        file_put_contents(base_path('routes/api.php'), $routesContent, FILE_APPEND);
        $this->info("$model Route added successfully.");
    }
}

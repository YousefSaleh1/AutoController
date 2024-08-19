<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait GenerateRoutes
{
    /**
     * Generate and add routes for a given model.
     *
     * This method generates and adds routes for a specific model to the API routes file.
     * The generated routes are for managing the specified model using RESTful conventions.
     *
     * @param string $model The name of the model for which the routes are being generated.
     *
     * @return void
     */
    protected function generateRoutes($model)
    {
        $models = Str::plural($model);
        $routesContent = "/**
     * {$model} Management Routes
     *
     * These routes handle {$model} management operations.
     */
    Route::apiResource('{$models}', App\Http\Controllers\\{$model}Controller::class);
        ";

        file_put_contents(base_path('routes/api.php'), $routesContent, FILE_APPEND);
        $this->info("$model Route added successfully.");
    }
}

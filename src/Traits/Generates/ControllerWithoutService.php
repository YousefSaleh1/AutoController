<?php

namespace CodingPartners\AutoController\Traits\Generates;

use CodingPartners\AutoController\Helpers\helper;
use Illuminate\Support\Str;

trait ControllerWithoutService
{

    /**
     * Generates a controller for a given model without using a service layer.
     *
     * This method creates a controller class for the specified model, including CRUD operations
     * and optionally soft delete operations if the model supports soft deletes. It also handles
     * file storage and validation using traits and form requests.
     *
     * @param string $model The name of the model for which the controller is being generated.
     * @param array $columns An array of column names for the specified model.
     * @param bool $softDelete Indicates whether the model uses soft deletes. If true, soft delete
     *                         methods will be generated in the controller.
     *
     * @return void
     *
     * @throws Exception If the controller file cannot be created.
     */
    protected function generateControllerWithoutService($model, array $columns, $softDelete)
    {
        $this->info("Generating CRUD without service for $model...");

        // Remove unwanted columns
        $columns = array_filter($columns, function ($column) use ($model) {
            // Common exclusions
            $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];

            // Additional exclusions for User model
            if ($model === 'User') {
                $excludedColumns = array_merge($excludedColumns, ['email_verified_at', 'remember_token']);
            }

            return !in_array($column, $excludedColumns);
        });

        $controllerName = $model . 'Controller';
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        $content = "<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\\$model;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use CodingPartners\AutoController\Traits\ApiResponseTrait;
use CodingPartners\AutoController\Traits\FileStorageTrait;
use App\Http\Resources\\{$model}Resource;
use App\Http\Requests\\{$model}Request\\Store{$model}Request;
use App\Http\Requests\\{$model}Request\\Update{$model}Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class {$controllerName} extends Controller
{
    use ApiResponseTrait, FileStorageTrait;

    {$this->generateIndex($model)}

    {$this->generateStore($model,$columns)}

    {$this->generateShow($model)}

    {$this->generateUpdate($model,$columns)}

    {$this->generateDestroy($model,$columns,$softDelete)}\n";

        if ($softDelete)
            $content .= "{$this->generateSoftDeleteMethods($model,$columns)}";

        $content .= "\n\n}";

        file_put_contents($controllerPath, $content);

        $this->info("Controller $controllerName created successfully.");
    }

    /**
     * Generate the index method for the specified model.
     *
     * This method generates the implementation of the index method for a given model.
     * It retrieves all the records for the specified model, paginated with a default page size of 10,
     * and returns a success response with the paginated model resources.
     *
     * @param string $model The name of the model.
     * @return string The generated index method code.
     */
    protected function generateIndex($model)
    {
        $models = Str::plural($model);

        return "/**
     * Display a listing of the resource.
     */
    public function index(Request \$request)
    {
        try {
            \$perPage = \$request->input('per_page', 10); // Default to 10 if not provided
            \${$models} = $model::paginate(\$perPage);
            return \$this->resourcePaginated({$model}Resource::collection(\${$models}));
        } catch (Exception \$e) {
            Log::error('Error Listing {$models} '. \$e->getMessage());
            return \$this->errorResponse(null,'there is something wrong in server',500);
        }
    }";
    }

    /**
     * Generate the store method for the specified model.
     *
     * This method generates the implementation of the store method for a given model.
     * It iterates through the provided columns, and for each column that is not 'id', 'created_at', or 'updated_at',
     * it adds an assignment for that column in the model creation array. If the column name ends with '_img',
     * it calls the `storeFile()` method to store the file and assigns the file path to the column.
     * Finally, it creates the model instance, returns a success response with the created model resource, and
     * includes a success message.
     *
     * @param string $model The name of the model.
     * @param array $columns The columns to be processed.
     * @return string The generated store method code.
     */
    protected function generateStore($model, array $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                    $suffix = helper::getSuffix($column);
                    $assignments .= "\n                '$column' => \$this->storeFile(\$request->$column, \"{$model}\", \"{$suffix}\"),";
                } else {
                    $assignments .= "\n                '$column' => \$request->$column,";
                }
            }
        }

        return "/**
     * Store a newly created resource in storage.
     */
    public function store(Store{$model}Request \$request)
    {
        try {
            \${$model} = {$model}::create([{$assignments}
            ]);
            return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Created Successfully\", 201);
        } catch (Exception \$e) {
            Log::error('Error creating {$model}: ' . \$e->getMessage());
            return \$this->errorResponse(null,'there is something wrong in server',500);
        }
    }";
    }

    /**
     * Generate the show method for the specified model.
     *
     * This method generates the implementation of the show method for a given model.
     * It returns a success response with the specified model resource.
     *
     * @param string $model The name of the model.
     * @return string The generated show method code.
     */
    protected function generateShow($model)
    {
        return "/**
     * Display the specified resource.
     */
    public function show($model \${$model})
    {
        try {
            return \$this->successResponse(new {$model}Resource(\${$model}));
        } catch (Exception \$e) {
            Log::error('Error retrieving {$model}: ' . \$e->getMessage());
            throw new Exception('Error retrieving {$model}.');
        }
    }";
    }

    /**
     * Generate the update method for the specified model.
     *
     * This method generates the implementation of the update method for a given model.
     * It iterates through the provided columns and constructs an array of update data.
     * For columns that end with '_img', it calls the `fileExists()` method to handle the file update.
     * For other columns, it simply assigns the corresponding field input value.
     * Finally, it updates the model instance with the constructed data and returns a success response.
     *
     * @param string $model The name of the model.
     * @param array $columns The columns to be processed.
     * @return string The generated update method code.
     */
    protected function generateUpdate($model, array $columns)
    {
        $assignments = "[";
        foreach ($columns as $column) {
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                    $suffix = helper::getSuffix($column);
                    $assignments .= "\n                \"$column\" => \$this->fileExists(\$request->$column, \${$model}->$column, \"{$model}\", \"{$suffix}\"),";
                } else {
                    $assignments .= "\n                \"$column\" => \$request->$column,";
                }
            }
        }
        $assignments .= "\n        ]";

        return "/**
     * Update the specified resource in storage.
     */
    public function update(Update{$model}Request \$request, $model \${$model})
    {
        try {
            \$data = {$assignments};
            \${$model}->update(array_filter(\$data));
            return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Updated Successfully\", 200);
        } catch (Exception \$e) {
            Log::error('Error updating {$model}: ' . \$e->getMessage());
            return \$this->errorResponse(null,'there is something wrong in server',500);
        }
    }";
    }

    /**
     * Generate the destroy method for the specified model.
     *
     * This method generates the implementation of the destroy method for a given model.
     * It iterates through the provided columns and checks if the column name ends with specific suffixes like '_img', '_vid', '_aud', or '_doc'.
     * If such columns are found and the model does not use soft deletes, it appends a call to the `deleteFile()` method to delete the associated files (e.g., images, videos, audios, documents).
     * Finally, it deletes the model instance and returns a success response.
     *
     * @param string $model The name of the model.
     * @param array $columns The columns to be processed.
     * @param bool $softDelete Indicates whether the model uses soft deletes.
     *                         If false, associated files (images, videos, audios, documents) will be deleted before deleting the model.
     *
     * @return string The generated destroy method code.
     */
    protected function generateDestroy($model, $columns, $softDelete)
    {
        $assignments = "";

        if (!$softDelete) {
            foreach ($columns as $column) {
                if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                    $assignments .= "\n        \$this->deleteFile(\${$model}->{$column});";
                }
            }
        }

        return "/**
     * Remove the specified resource from storage.
     */
    public function destroy($model \${$model})
    {{$assignments}
        \${$model}->delete();
        return \$this->successResponse(null, \"{$model} Deleted Successfully\");
    }\n\n";
    }

    /**
     * Generate the soft delete-related methods for the specified model.
     *
     * This method generates the implementation of methods related to soft deletes for a given model.
     * It creates the following methods:
     *  - A method to retrieve soft-deleted (trashed) records.
     *  - A method to restore soft-deleted records.
     *  - A method to permanently delete (force delete) soft-deleted records, including handling any associated files (e.g., images, videos, audios, documents).
     *
     * @param string $model The name of the model for which the soft delete methods are being generated.
     * @param array $columns The columns of the model, used to handle associated files during force delete.
     *
     * @return string The generated soft delete-related methods code.
     */
    protected function generateSoftDeleteMethods($model, $columns)
    {
        return "
    {$this->generateTrashedMethods($model)}

    {$this->generateRestoreMethod($model)}

    {$this->generateForceDeleteMethod($model,$columns)}
";
    }

    /**
     * Generate the method for retrieving trashed (soft deleted) resources for the specified model.
     *
     * This method creates the `trashed` method for a given model's controller, which retrieves and
     * paginates a list of soft-deleted (trashed) resources. The paginated data is then returned as
     * a resource collection.
     *
     * @param string $model The name of the model for which the trashed method is being generated.
     *
     * @return string The generated trashed method code.
     */
    protected function generateTrashedMethods($model)
    {
        $models = Str::plural($model);
        return "/**
     * Display a paginated listing of the trashed (soft deleted) resources.
     */
    public function trashed(Request \$request)
    {
        try {
            \$perPage = \$request->input('per_page', 10);
            \$trashed{$models} = {$model}::onlyTrashed()->paginate(\$perPage);
            return \$this->resourcePaginated({$model}Resource::collection(\$trashed{$models}));
        } catch (Exception \$e) {
            Log::error('Error Trashing {$model} '. \$e->getMessage());
            return \$this->errorResponse(null,'there is something wrong in server',500);
        }
    }";
    }

    /**
     * Generate the method for restoring a trashed (soft deleted) resource for the specified model.
     *
     * This method creates the `restore` method for a given model's controller, which allows restoring
     * a soft-deleted resource by its ID. If the resource is successfully restored, a success response
     * with the restored resource data is returned. If the resource is not found or an error occurs during
     * the restoration process, appropriate exceptions are thrown.
     *
     * @param string $model The name of the model for which the restore method is being generated.
     *
     * @return string The generated restore method code.
     */
    protected function generateRestoreMethod($model)
    {
        return "/**
     * Restore a trashed (soft deleted) resource by its ID.
     */
    public function restore(\$id)
    {
        try{
            \${$model} = {$model}::onlyTrashed()->findOrFail(\$id);
            \${$model}->restore();
            return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} restored Successfully\");
        } catch (ModelNotFoundException \$e) {
            Log::error('{$model} not found: ' . \$e->getMessage());
            throw new Exception('{$model} not found.');
        } catch (Exception \$e) {
            Log::error('Error restoring {$model}: ' . \$e->getMessage());
            return \$this->errorResponse(null,'there is something wrong in server',500);
        }
    }";
    }

    /**
     * Generate the method for permanently deleting a trashed (soft deleted) resource for the specified model.
     *
     * This method creates the `forceDelete` method for a given model's controller, allowing the permanent
     * deletion of a soft-deleted resource by its ID. Before deletion, it checks if the resource contains
     * any associated files (e.g., images, videos, audio, documents) and deletes them. If the resource is
     * successfully deleted, a success response is returned. If the resource is not found or an error occurs
     * during the deletion process, appropriate exceptions are thrown.
     *
     * @param string $model The name of the model for which the force delete method is being generated.
     * @param array $columns The columns of the model, used to determine if any associated files should be deleted.
     *
     * @return string The generated force delete method code.
     */
    protected function generateForceDeleteMethod($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                $assignments .= "\n        \$this->deleteFile(\${$model}->{$column});";
            }
        }

        return "/**
     * Permanently delete a trashed (soft deleted) resource by its ID.
     */
    public function forceDelete(\$id)
    {
        try{
            \${$model} = {$model}::onlyTrashed()->findOrFail(\$id);
            {$assignments}
            \${$model}->forceDelete();
            return \$this->successResponse(null, \"{$model} deleted Permanently\");
        } catch (ModelNotFoundException \$e) {
            Log::error('{$model} not found: ' . \$e->getMessage());
            throw new Exception('{$model} not found.');
        } catch (Exception \$e) {
            Log::error('Error force deleting {$model} '. \$e->getMessage());
            return \$this->errorResponse(null,'there is something wrong in server',500);
        }
    }";
    }
}

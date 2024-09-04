<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait ControllerWithService
{
    /**
     * Generates a controller for a given model.
     *
     * This method creates a controller class for the specified model, including CRUD operations.
     * It also handles file storage and validation using traits and form requests.
     *
     * @param string $model The name of the model for which the controller is being generated.
     * @param array $columns An array of column names for the specified model.
     *
     * @return void
     *
     * @throws Exception If the controller file cannot be created.
     */
    protected function generateControllerWithService($model, array $columns, $softDeleteMethods)
    {
        $this->info("Generating CRUD with service for $model...");

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

use App\Models\\$model;
use Illuminate\Http\Request;
use App\Services\\{$model}Service;
use App\Http\Resources\\{$model}Resource;
use CodingPartners\AutoController\Traits\ApiResponseTrait;
use App\Http\Requests\\{$model}Request\\Store{$model}Request;
use App\Http\Requests\\{$model}Request\\Update{$model}Request;

class {$controllerName} extends Controller
{
    use ApiResponseTrait;

    /**
     * @var {$model}Service
     */
    protected \${$model}Service;

    /**
     *  {$model}Controller constructor
     * @param {$model}Service \${$model}Service
     */
    public function __construct({$model}Service \${$model}Service){
        \$this->{$model}Service = \${$model}Service;
    }

    {$this->generateIndexMethod($model)}

    {$this->generateStoreMethod($model,$columns)}

    {$this->generateShowMethod($model)}

    {$this->generateUpdateMethod($model,$columns)}

    {$this->generateDestroyMethod($model,$columns,$softDeleteMethods)}\n";

        if ($softDeleteMethods)
            $content .= "{$this->generateSoftDeleteMethodsWithService($model)}";

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
    protected function generateIndexMethod($model)
    {
        $models = Str::plural($model);

        return "/**
     * Display a listing of the resource.
     */
    public function index(Request \$request)
    {
        \$perPage = \$request->input('per_page', 10); // Default to 10 if not provided
        \${$models} = \$this->{$model}Service->list{$model}(\$perPage);
        return \$this->resourcePaginated({$model}Resource::collection(\${$models}));
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
    protected function generateStoreMethod($model, array $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                if (Str::endsWith($column, '_img')) {
                    $assignments .= "\n            '$column' => \$this->storeFile(\$request->$column, \"{$model}\"),";
                } else {
                    $assignments .= "\n            '$column' => \$request->$column,";
                }
            }
        }

        return "/**
     * Store a newly created resource in storage.
     */
    public function store(Store{$model}Request \$request)
    {
        \$fieldInputs = \$request->validated();
        \${$model}    = \$this->{$model}Service->create{$model}(\$fieldInputs);
        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Created Successfully\", 201);
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
    protected function generateShowMethod($model)
    {
        return "/**
     * Display the specified resource.
     */
    public function show($model \${$model})
    {
        return \$this->successResponse(new {$model}Resource(\${$model}));
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
    protected function generateUpdateMethod($model, array $columns)
    {
        $assignments = "[";
        foreach ($columns as $column) {
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                if (Str::endsWith($column, '_img')) {
                    $assignments .= "\n        \"$column\" => \$this->fileExists(\$fieldInputs[\"$column\"], \${$model}->$column, \"{$model}\"),";
                } else {
                    $assignments .= "\n        \"$column\" => \$fieldInputs[\"$column\"],";
                }
            }
        }
        $assignments .= "\n        ]";

        return "/**
     * Update the specified resource in storage.
     */
    public function update(Update{$model}Request \$request, $model \${$model})
    {
        \$fieldInputs = \$request->validated();
        \${$model}    = \$this->{$model}Service->update{$model}(\$fieldInputs, \${$model});
        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Updated Successfully\", 200);
    }";
    }

    /**
     * Generate the destroy method for the specified model.
     *
     * This method generates the code for the `destroy` method in the controller of a given model.
     * The generated method is responsible for deleting a specific resource instance from storage.
     * The method utilizes the model's corresponding service class to perform the deletion.
     * Upon successful deletion, a success response is returned.
     *
     * @param string $model The name of the model for which the destroy method is being generated.
     * @return string The generated destroy method code.
     */
    protected function generateDestroyMethod($model)
    {
        return "/**
     * Remove the specified resource from storage.
     */
    public function destroy($model \${$model})
    {
        \$this->{$model}Service->delete{$model}(\${$model});
        return \$this->successResponse(null, \"{$model} Deleted Successfully\");
    }\n\n";
    }

    /**
     * Generate soft delete-related methods for the specified model.
     *
     * This method generates the code for handling soft deletion functionality in a controller.
     * The generated methods include:
     * - `trashed`: Lists the soft deleted resources.
     * - `restore`: Restores a soft deleted resource.
     * - `forceDelete`: Permanently deletes a soft deleted resource.
     *
     * The generated methods are added to the controller to provide comprehensive soft delete management.
     *
     * @param string $model The name of the model for which soft delete methods are being generated.
     * @return string The generated code for the soft delete-related methods.
     */
    protected function generateSoftDeleteMethodsWithService($model)
    {
        return "
    {$this->generateTrashedMethodsWithService($model)}

    {$this->generateRestoreMethodWithService($model)}

    {$this->generateForceDeleteMethodWithService($model)}
";
    }

    /**
     * Generate the trashed method for the specified model.
     *
     * This method generates the code for the `trashed` method in the controller of a given model.
     * The generated method is responsible for displaying a paginated listing of trashed (soft-deleted) resources.
     * It uses the model's corresponding service class to retrieve the list of trashed resources and paginates the results.
     * The paginated results are then returned in a response with the appropriate resource formatting.
     *
     * @param string $model The name of the model for which the trashed method is being generated.
     * @return string The generated trashed method code.
     */
    protected function generateTrashedMethodsWithService($model)
    {
        $models = Str::plural($model);
        return "/**
     * Display a paginated listing of the trashed (soft deleted) resources.
     */
    public function trashed(Request \$request)
    {
        \$perPage = \$request->input('per_page', 10);
        \$trashed{$models} = \$this->{$model}Service->trashedList{$model}(\$perPage);
        return \$this->resourcePaginated({$model}Resource::collection(\$trashed{$models}));
    }";
    }

    /**
     * Generate the restore method for the specified model.
     *
     * This method generates the code for the `restore` method in the controller of a given model.
     * The generated method is responsible for restoring a trashed (soft-deleted) resource by its ID.
     * It utilizes the model's corresponding service class to perform the restoration operation.
     * Upon successful restoration, a success response is returned with the restored resource.
     *
     * @param string $model The name of the model for which the restore method is being generated.
     * @return string The generated restore method code.
     */
    protected function generateRestoreMethodWithService($model)
    {
        return "/**
     * Restore a trashed (soft deleted) resource by its ID.
     */
    public function restore(\$id)
    {
        \${$model} = \$this->{$model}Service->restore{$model}(\$id);
        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} restored Successfully\");
    }";
    }

    /**
     * Generate the force delete method for the specified model.
     *
     * This method generates the code for the `forceDelete` method in the controller of a given model.
     * The generated method is responsible for permanently deleting a specific resource instance by its ID.
     * It utilizes the model's corresponding service class to perform the permanent deletion.
     * Upon successful deletion, a success response is returned to indicate that the resource has been permanently deleted.
     *
     * @param string $model The name of the model for which the force delete method is being generated.
     * @return string The generated force delete method code.
     */
    protected function generateForceDeleteMethodWithService($model)
    {
        return "/**
     * Permanently delete a trashed (soft deleted) resource by its ID.
     */
    public function forceDelete(\$id)
    {
        \$this->{$model}Service->forceDelete{$model}(\$id);
        return \$this->successResponse(null, \"{$model} deleted Permanently\");

    }";
    }
}

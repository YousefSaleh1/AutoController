<?php

namespace CodingPartners\AutoController\Traits\Generates;

use CodingPartners\AutoController\Helpers\helper;
use Illuminate\Support\Str;

trait ControllerWithoutService {

    /**
     * Generates a controller for a given model without using a service layer.
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
    protected function generateControllerWithoutService($model, array $columns)
    {
        // Remove unwanted columns
        $columns = array_filter($columns, function($column) use ($model) {
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

class {$controllerName} extends Controller
{
    use ApiResponseTrait, FileStorageTrait;

    {$this->generateIndex($model)}

    {$this->generateStore($model, $columns)}

    {$this->generateShow($model)}

    {$this->generateUpdate($model, $columns)}

    {$this->generateDestroy($model)}
}";

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
        return "/**
     * Display a listing of the resource.
     */
    public function index(Request \$request)
    {
        try {
            \$perPage = \$request->input('per_page', 10); // Default to 10 if not provided
            \${$model} = $model::paginate(\$perPage);
            return \$this->resourcePaginated({$model}Resource::collection(\${$model}));
        } catch (Exception \$e) {
            Log::error('Error Listing {$model} '. \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
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
                    $suffix= helper::getSuffix($column);
                    $assignments .= "\n            '$column' => \$this->storeFile(\$request->$column, \"{$model}\", \"{$suffix}\"),";
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
        try {
            \${$model} = {$model}::create([{$assignments}
            ]);
            return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Created Successfully\", 201);
        } catch (Exception \$e) {
            Log::error('Error creating {$model}: ' . \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
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
                    $suffix= helper::getSuffix($column);
                    $assignments .= "\n        \"$column\" => \$this->fileExists(\$request->$column, \${$model}->$column, \"{$model}\", \"{$suffix}\"),";
                } else {
                    $assignments .= "\n        \"$column\" => \$request->$column,";
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
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
        }
    }";
    }

    /**
     * Generate the destroy method for the specified model.
     *
     * This method generates the implementation of the destroy method for a given model.
     * It deletes the model instance and returns a success response.
     *
     * @param string $model The name of the model.
     * @return string The generated destroy method code.
     */
    protected function generateDestroy($model)
    {
        return "/**
     * Remove the specified resource from storage.
     */
    public function destroy($model \${$model})
    {
        try {
            \${$model}->delete();
            return \$this->successResponse(null, \"{$model} Deleted Successfully\");
        } catch (Exception \$e) {
            Log::error('Error deleting {$model} '. \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
        }
    }";
    }
}

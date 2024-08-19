<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait GenerateContoller
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
    protected function generateController($model, array $columns)
    {
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

    {$this->generateDestroyMethod($model,$columns)}
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
    protected function generateIndexMethod($model)
    {
        $models = Str::plural($model);

        return "/**
     * Display a listing of the resource.
     */
    public function index()
    {
        \${$models} = \$this->{$model}Service->list{$model}();
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
        \$fieldInputs = \$request->valedated();
        \${$model}    = \$this->{$model}Service->update{$model}(\$fieldInputs, \${$model});
        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Updated Successfully\", 200);
    }";
    }

    /**
     * Generate the destroy method for the specified model.
     *
     * This method generates the implementation of the destroy method for a given model.
     * It iterates through the provided columns and checks if the column name ends with '_img'.
     * If it does, it appends a call to the `deletePhoto()` method to delete the associated photo.
     * Finally, it deletes the model instance and returns a success response.
     *
     * @param string $model The name of the model.
     * @param array $columns The columns to be processed.
     * @return string The generated destroy method code.
     */
    protected function generateDestroyMethod($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (Str::endsWith($column, '_img')) {
                $assignments .= "\n        \$this->deleteFile(\${$model}->{$column});";
            }
        }

        return "/**
     * Remove the specified resource from storage.
     */
    public function destroy($model \${$model})
    {
        \$this->{$model}Service->delete{$model}(\${$model});
        return \$this->successResponse(null, \"{$model} Deleted Successfully\");
    }\n\n";
    }
}

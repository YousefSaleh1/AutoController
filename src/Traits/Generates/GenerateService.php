<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait GenerateService
{
    /**
     * Generate a service class for the specified model.
     *
     * This method generates a service class for the given model. It checks if the
     * "App\Services" directory exists, and if not, creates it. It then checks if
     * the service class file exists, and if not, creates it with the necessary
     * methods for listing, creating, retrieving, updating, and deleting the model.
     *
     * @param string $model The name of the model.
     * @param array $columns The columns of the model.
     * @return void
     */
    protected function generateService($model, $columns)
    {
        $serviceName = $model . 'Service';
        $sevicePath = app_path("Services/{$serviceName}.php");

        // Check if the App\Http\Requests\{Model}Request directory exists, if not, create it
        if (!is_dir(app_path("Services"))) {
            mkdir(app_path("Services"), 0755, true);
        }

        // Check if the Resource class file exists, if not, create it
        if (!file_exists($sevicePath)) {

            $srviceContent = "<?php

namespace App\Services;

use Exception;
use App\Models\\$model;
use Illuminate\Support\Facades\Log;
use CodingPartners\AutoController\Traits\ApiResponseTrait;
use CodingPartners\AutoController\Traits\FileStorageTrait;

class {$serviceName}
{
    use ApiResponseTrait, FileStorageTrait;

    {$this->generateListMethodInService($model)}

    {$this->generateCreateMethodInService($model,$columns)}

    {$this->generategetMethodInService($model)}

    {$this->generateUpdateMethodInService($model,$columns)}

    {$this->generateDeleteMethodInService($model,$columns)}
}\n         ";
            file_put_contents($sevicePath, $srviceContent);
            $this->info("Service $serviceName created successfully.");
        }
    }

    /**
     * Generate the list method for the specified model.
     *
     * This method generates the implementation of the list method for a given model.
     * It retrieves all the records for the model using the `paginate()` method and
     * returns the result. If an exception occurs, it logs the error and throws a
     * new exception with an error response.
     *
     * @param string $model The name of the model.
     * @return string The generated list method code.
     */
    protected function generateListMethodInService($model)
    {
        $models = Str::plural($model);

        return "/**
     * list all {$models} information
     */
    public function list{$model}() {
        try {
            return {$model}::paginate(10);
        } catch (Exception \$e) {
            Log::error('Error Listing {$model} '. \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
        }
    }";
    }

    /**
     * Create a new {$model}.
     *
     * This method creates a new instance of the {$model} model and saves it to the database.
     * It iterates through the provided $columns array, excluding the 'id', 'created_at', and 'updated_at' columns.
     * If the column name ends with '_img', it calls the `storeFile()` method to store the file and assigns the file path to the corresponding column.
     * Otherwise, it assigns the value from the $fieldInputs array to the corresponding column.
     * Finally, it creates the new {$model} instance and returns it.
     *
     * @param array $fieldInputs An associative array containing the input values for the new {$model} instance.
     * @return \App\Models\\{$model} The newly created {$model} instance.
     */
    protected function generateCreateMethodInService($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                if (Str::endsWith($column, '_img')) {
                    $assignments .= "\n            '$column' => \$this->storeFile(\$fieldInputs[\"$column\"], \"{$model}\"),";
                } else {
                    $assignments .= "\n            '$column' => \$fieldInputs[\"$column\"],";
                }
            }
        }

        return "/**
     * Create a new {$model}.
     * @param array \$fieldInputs
     * @return \App\Models\\{$model}
     */
    public function create{$model}(array \$fieldInputs){
        try {
            return {$model}::create([{$assignments}
            ]);
        } catch (Exception \$e) {
            Log::error('Error creating {$model}: ' . \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
        }
    }
    ";
    }

    /**
     * Generate the get method for the specified model.
     *
     * This method generates the implementation of the get method for a given model.
     * It takes a {$model} instance as a parameter and returns the same instance.
     * The method is designed to handle any exceptions that may occur during the retrieval process,
     * and it logs the error message to the system log.
     *
     * @param \App\Models\\{$model} \${$model} The model instance to be retrieved.
     * @return \App\Models\\{$model} The retrieved model instance.
     */
    protected function generategetMethodInService($model)
    {
        return "/**
     * Get the details of a specific {$model}.
     *
     * @param \App\Models\\{$model} \${$model}
     * @return \App\Models\\{$model}
     */
    public function get{$model}({$model} \${$model})
    {
        try {
            return \${$model};
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
     * It takes an array of field inputs and a model instance as parameters.
     * The method iterates through the provided columns, skipping the 'id', 'created_at', and 'updated_at' columns.
     * For each column, it checks if the column name ends with '_img'. If so, it appends a call to the `fileExists()` method to handle the file upload.
     * Otherwise, it adds the field input value to the data array.
     * Finally, the method updates the model instance with the filtered data array and returns the updated model instance.
     * The method is designed to handle any exceptions that may occur during the update process and logs the error message to the system log.
     *
     * @param array $fieldInputs The array of field inputs to be updated.
     * @param \App\Models\\{$model} \${$model} The model instance to be updated.
     * @return \App\Models\\{$model} The updated model instance.
     */
    protected function generateUpdateMethodInService($model, $columns)
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
     * Update a specific {$model}.
     *
     * @param array \$fieldInputs
     * @param {$model} \${$model}
     * @return \App\Models\\{$model}
     */
    public function update{$model}(array \$fieldInputs, \${$model}) {
        try {
            \$data = {$assignments};
            \${$model}->update(array_filter(\$data));
            return \${$model};
        } catch (Exception \$e) {
            Log::error('Error updating {$model}: ' . \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
        }
    }";
    }

    /**
     * Generate the delete method for the specified model.
     *
     * This method generates the implementation of the delete method for a given model.
     * It iterates through the provided columns and checks if the column name ends with '_img'.
     * If it does, it appends a call to the `deleteFile()` method to delete the associated file.
     * Finally, it deletes the model instance.
     * The method is designed to handle any exceptions that may occur during the delete process and logs the error message to the system log.
     *
     * @param \App\Models\\{$model} \${$model} The model instance to be deleted.
     * @return void
     */
    protected function generateDeleteMethodInService($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (Str::endsWith($column, '_img')) {
                $assignments .= "\n        \$this->deleteFile(\${$model}->{$column});";
            }
        }

        return "/**
     * Delete a specific {$model}.
     *
     * @param {$model} \${$model}
     * @return void
     */
    public function delete{$model}(\${$model}){
        try {{$assignments}
            \${$model}->delete();
        } catch (Exception \$e) {
            Log::error('Error deleting {$model} '. \$e->getMessage());
            throw new Exception(\$this->errorResponse(null,'there is something wrong in server',500));
        }
    }";
    }
}

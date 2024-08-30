<?php

namespace CodingPartners\AutoController\Traits\Generates;

use CodingPartners\AutoController\Helpers\helper;
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
    protected function generateService($model, $columns, $softDeleteMethods)
    {
        $serviceName = $model . 'Service';
        $sevicePath = app_path("Services/{$serviceName}.php");

        // Check if the App\Http\Requests\{Model}Request directory exists, if not, create it
        if (!is_dir(app_path("Services"))) {
            mkdir(app_path("Services"), 0755, true);
        }

        // Check if the Resource class file exists, if not, create it
        if (!file_exists($sevicePath)) {

            $this->info("Generating Service for $model...");

            $srviceContent = "<?php

namespace App\Services;

use Exception;
use App\Models\\$model;
use Illuminate\Support\Facades\Log;
use CodingPartners\AutoController\Traits\ApiResponseTrait;
use CodingPartners\AutoController\Traits\FileStorageTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class {$serviceName}
{
    use ApiResponseTrait, FileStorageTrait;

    {$this->generateListMethodInService($model)}

    {$this->generateCreateMethodInService($model,$columns)}

    {$this->generategetMethodInService($model)}

    {$this->generateUpdateMethodInService($model,$columns)}

    {$this->generateDeleteMethodInService($model,$columns,$softDeleteMethods)}\n";

            if ($softDeleteMethods) {
                $srviceContent .= "{$this->generateSoftDeleteMethodsInService($model,$columns)}\n\n}";
            }
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
    public function list{$model}(int \$perPage) {
        try {
            return {$model}::paginate(\$perPage);
        } catch (Exception \$e) {
            Log::error('Error Listing {$model} '. \$e->getMessage());
            throw new Exception('there is something wrong in server');
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
            if (!in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                    $suffix = helper::getSuffix($column);
                    $assignments .= "\n                    '$column' => \$this->storeFile(\$fieldInputs[\"$column\"], \"{$model}\", \"{$suffix}\"),";
                } else {
                    $assignments .= "\n                    '$column' => \$fieldInputs[\"$column\"],";
                }
            }
        }

        return "/**
     * Create a new {$model}.
     * @param array \$fieldInputs
     * @return \App\Models\\{$model}
     */
    public function create{$model}(array \$fieldInputs)
    {
        try{
            return {$model}::create([{$assignments}
            ]);
        } catch (Exception \$e) {
            Log::error('Error creating {$model}: ' . \$e->getMessage());
            throw new Exception('there is something wrong in server');
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
            if (!in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                    $suffix = helper::getSuffix($column);
                    $assignments .= "\n                    \"$column\" => \$this->fileExists(\$fieldInputs[\"$column\"], \${$model}->$column, \"{$model}\", \"{$suffix}\"),";
                } else {
                    $assignments .= "\n                    \"$column\" => \$fieldInputs[\"$column\"],";
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
            throw new Exception('there is something wrong in server');
        }
    }";
    }

    /**
     * Generate the delete method for the specified model.
     *
     * This method generates the implementation of the `delete` method for a given model within a service class.
     * It iterates through the provided columns and checks if any column name ends with specific suffixes like '_img', '_vid', '_aud', or '_doc'.
     * If such columns are found and the model does not use soft deletes, it appends a call to the `deleteFile()` method to delete the associated files (e.g., images, videos, audios, documents).
     * After handling any file deletions, the method proceeds to delete the model instance itself.
     * The method is designed to handle any exceptions that may occur during the delete process. If an error occurs,
     * it logs the error message to the system log and throws a new exception with a generic error response.
     *
     * @param string $model The name of the model for which the delete method is being generated.
     * @param array $columns An array of column names to be checked for associated files.
     * @param bool $softDelete Indicates whether the model uses soft deletes.
     *                         If false, associated files will be deleted before deleting the model instance.
     *
     * @return string The generated delete method code.
     */
    protected function generateDeleteMethodInService($model, $columns, $softDelete)
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
            throw new Exception('there is something wrong in server');
        }
    }";
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
    protected function generateSoftDeleteMethodsInService($model, $columns)
    {
        return "
    {$this->generateTrashedMethodsInService($model)}

    {$this->generateRestoreMethodInService($model)}

    {$this->generateForceDeleteMethodInService($model,$columns)}
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
    protected function generateTrashedMethodsInService($model)
    {
        return "/**
     * Display a paginated listing of the trashed (soft deleted) resources.
     */
    public function trashedList{$model}(\$perPage)
    {
        try {
            return {$model}::onlyTrashed()->paginate(\$perPage);
        } catch (Exception \$e) {
            Log::error('Error Trashing {$model} '. \$e->getMessage());
            throw new Exception('there is something wrong in server');
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
    protected function generateRestoreMethodInService($model)
    {
        return "/**
     * Restore a trashed (soft deleted) resource by its ID.
     *
     * @param  int  \$id  The ID of the trashed {$model} to be restored.
     * @return \App\Models\\{$model}
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the {$model} with the given ID is not found.
     * @throws \Exception If there is an error during the restore process.
     */
    public function restore{$model}(\$id)
    {
        try{
            \${$model} = {$model}::onlyTrashed()->findOrFail(\$id);
            \${$model}->restore();
            return \${$model};
        } catch (ModelNotFoundException \$e) {
            Log::error('{$model} not found: ' . \$e->getMessage());
            throw new Exception('{$model} not found.');
        } catch (Exception \$e) {
            Log::error('Error restoring {$model}: ' . \$e->getMessage());
            throw new Exception('there is something wrong in server');
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
    protected function generateForceDeleteMethodInService($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if (Str::endsWith($column, '_img') || Str::endsWith($column, '_vid') || Str::endsWith($column, '_aud') || Str::endsWith($column, '_doc')) {
                $assignments .= "\n        \$this->deleteFile(\${$model}->{$column});";
            }
        }

        return "/**
     * Permanently delete a trashed (soft deleted) resource by its ID.
     *
     * @param  int  \$id  The ID of the trashed {$model} to be permanently deleted.
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the {$model} with the given ID is not found.
     * @throws \Exception If there is an error during the force delete process.
     */
    public function forceDelete{$model}(\$id)
    {
        try{
            \${$model} = {$model}::onlyTrashed()->findOrFail(\$id);
            {$assignments}
            \${$model}->forceDelete();
        } catch (ModelNotFoundException \$e) {
            Log::error('{$model} not found: ' . \$e->getMessage());
            throw new Exception('{$model} not found.');
        } catch (Exception \$e) {
            Log::error('Error force deleting {$model} '. \$e->getMessage());
            throw new Exception('there is something wrong in server');
        }
    }";
    }
}

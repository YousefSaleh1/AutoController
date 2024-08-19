<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait GenerateResource
{
    /**
     * Generates a Resource class for the given model.
     *
     * This method creates a new Resource class file for the specified model.
     * It first filters out unwanted columns from the given array of columns.
     * Then, it constructs the file path for the Resource class and checks if the directory exists.
     * If not, it creates the directory.
     * Next, it checks if the Resource class file already exists. If not, it creates the file with the necessary content.
     * The content includes the class definition, the toArray method, and assignments for each column.
     *
     * @param string $model The name of the model for which the Resource class is being generated.
     * @param array $columns An array of column names for the specified model.
     *
     * @return void
     */
    protected function generateResource($model, array $columns)
    {
        // Remove unwanted columns
        $columns = array_filter($columns, function ($column) use ($model) {
            // Common exclusions
            $excludedColumns = ['created_at', 'updated_at', 'deleted_at'];

            // Additional exclusions for User model
            if ($model === 'User') {
                $excludedColumns = array_merge($excludedColumns, ['password', 'email_verified_at', 'remember_token']);
            }

            return !in_array($column, $excludedColumns);
        });

        $resourceName = $model . 'Resource';
        $resourcePath = app_path("Http/Resources/{$resourceName}.php");

        // Check if the App\Http\Resources directory exists, if not, create it
        if (!is_dir(app_path("Http/Resources"))) {
            mkdir(app_path("Http/Resources"), 0755, true);
        }

        // Check if the Resource class file exists, if not, create it
        if (!file_exists($resourcePath)) {

            $assignments = "";
            foreach ($columns as $column) {
                if (!Str::endsWith($column, '_img')) {
                    $assignments .= "\n            '{$column}' => \$this->$column,";
                } else {
                    $assignments .= "\n            '{$column}' => asset(\$this->$column),";
                }
            }

            $resourceContent = "<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {$resourceName} extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  \$request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(\$request)
    {
        return [            {$assignments}
        ];
    }
}\n";
            file_put_contents($resourcePath, $resourceContent);
            $this->info("Resource $resourceName created successfully.");
        }
    }
}

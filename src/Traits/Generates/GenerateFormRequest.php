<?php

namespace CodingPartners\AutoController\Traits\Generates;

use Illuminate\Support\Str;

trait GenerateFormRequest
{
    /**
     * Generates a Store FormRequest class for the given model.
     *
     * This method creates a new Store FormRequest class file for the specified model.
     * It first filters out unwanted columns from the given array of columns.
     * Then, it constructs the file path for the Store FormRequest class and checks if the directory exists.
     * If not, it creates the directory.
     * Next, it checks if the Store FormRequest class file already exists. If not, it creates the file with the necessary content.
     * The content includes the class definition, the authorize method, and the rules method, which define the validation rules.
     *
     * @param string $model The name of the model for which the Store FormRequest class is being generated.
     * @param array $columns An array of column names for the specified model.
     *
     * @return void
     */
    protected function generateStoreFormRequest($model, array $columns)
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

        // Create folder for model requests
        $folderName = "{$model}Request";
        $requestName = "Store{$model}Request";
        $requestPath = app_path("Http/Requests/{$folderName}/{$requestName}.php");

        // Check if the App\Http\Requests\{Model}Request directory exists, if not, create it
        if (!is_dir(app_path("Http/Requests/{$folderName}"))) {
            mkdir(app_path("Http/Requests/{$folderName}"), 0755, true);
        }

        // Check if the FormRequest class file exists, if not, create it
        if (!file_exists($requestPath)) {
            // Generate validation rules
            $rules = "";
            foreach ($columns as $column) {
                if (Str::endsWith($column, '_img')) {
                    $rules .= "\n            '{$column}' => 'required|file|image|mimes:png,jpg,jpeg,jfif|max:10000|mimetypes:image/jpeg,image/png,image/jpg,image/jfif',";
                } else {
                    $rules .= "\n            '{$column}' => ['required'],";
                }
            }
            $requestContent = "<?php

namespace App\Http\Requests\\{$folderName};

use Illuminate\Foundation\Http\FormRequest;

class {$requestName} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [{$rules}
        ];
    }
}\n";
            file_put_contents($requestPath, $requestContent);
            $this->info("FormRequest $requestName created successfully in folder $folderName.");
        }
    }

    /**
     * Generates a Update FormRequest class for the given model.
     *
     * This method creates a new Update FormRequest class file for the specified model.
     * It first filters out unwanted columns from the given array of columns.
     * Then, it constructs the file path for the Update FormRequest class and checks if the directory exists.
     * If not, it creates the directory.
     * Next, it checks if the Update FormRequest class file already exists. If not, it creates the file with the necessary content.
     * The content includes the class definition, the authorize method, and the rules method, which define the validation rules.
     *
     * @param string $model The name of the model for which the Update FormRequest class is being generated.
     * @param array $columns An array of column names for the specified model.
     *
     * @return void
     */
    protected function generateUpdateFormRequest($model, array $columns)
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

        // Create folder for model requests
        $folderName = "{$model}Request";
        $requestName = "Update{$model}Request";
        $requestPath = app_path("Http/Requests/{$folderName}/{$requestName}.php");

        // Check if the App\Http\Requests\{Model}Request directory exists, if not, create it
        if (!is_dir(app_path("Http/Requests/{$folderName}"))) {
            mkdir(app_path("Http/Requests/{$folderName}"), 0755, true);
        }

        // Check if the FormRequest class file exists, if not, create it
        if (!file_exists($requestPath)) {
            // Generate validation rules
            $rules = "";
            foreach ($columns as $column) {
                if (Str::endsWith($column, '_img')) {
                    $rules .= "\n            '{$column}' => 'nullable|file|image|mimes:png,jpg,jpeg,jfif|max:10000|mimetypes:image/jpeg,image/png,image/jpg,image/jfif',";
                } else {
                    $rules .= "\n            '{$column}' => ['nullable'],";
                }
            }
            $requestContent = "<?php

namespace App\Http\Requests\\{$folderName};

use Illuminate\Foundation\Http\FormRequest;

class {$requestName} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [{$rules}
        ];
    }
}\n";
            file_put_contents($requestPath, $requestContent);
            $this->info("FormRequest $requestName created successfully in folder $folderName.");
        }
    }
}

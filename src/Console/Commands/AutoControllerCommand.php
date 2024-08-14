<?php

namespace CodingPartners\AutoController\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutoControllerCommand extends Command
{
    protected $signature = 'crud:generate {model}';
    protected $description = 'Generate CRUD operations for a model';

    public function handle()
    {
        $model = $this->argument('model');
        $tableName = Str::snake(Str::plural($model));

        if (!Schema::hasTable($tableName)) {
            $this->error("Table $tableName does not exist.");
            return;
        }

        $columns = Schema::getColumnListing($tableName);

        $this->info("Generating store FormRequest for $model...");
        $this->generateUpdateFormRequest($model, $columns);

        $this->info("Generating update FormRequest for $model...");
        $this->generateFormRequest($model, $columns);

        $this->info("Generating CRUD for $model...");
        $this->generateController($model, $columns);

        $this->info("Generating Resource for $model...");
        $this->generateResource($model, $columns);

        $this->info("Generating routes/api.php for $model...");
        $this->generateRoutes($model);

    }

    protected function generateResource($model, array $columns)
    {
        // Remove unwanted columns
        $columns = array_filter($columns, function($column) use ($model) {
            // Common exclusions
            $excludedColumns = ['created_at', 'updated_at', 'deleted_at'];

            // Additional exclusions for User model
            if ($model === 'User') {
                $excludedColumns = array_merge($excludedColumns, ['email_verified_at', 'remember_token']);
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
                $assignments .= "\n            '{$column}' => \$this->$column,";
            }

            $resourceContent = "<?php\n\nnamespace App\Http\Resources;\n\nuse Illuminate\Http\Resources\Json\JsonResource;\n\nclass {$resourceName} extends JsonResource\n{\n    /**\n     * Transform the resource into an array.\n     *\n     * @param  \Illuminate\Http\Request  \$request\n     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable\n     */\n    public function toArray(\$request)\n    {\n        return [            {$assignments}\n        ];\n    }\n}\n";

            file_put_contents($resourcePath, $resourceContent);
            $this->info("Resource $resourceName created successfully.");
        }
    }

    protected function generateFormRequest($model, array $columns)
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
            $requestContent = "<?php\n\nnamespace App\Http\Requests\\{$folderName};\n\nuse Illuminate\Foundation\Http\FormRequest;\n\nclass {$requestName} extends FormRequest\n{\n    /**\n     * Determine if the user is authorized to make this request.\n     *\n     * @return bool\n     */\n    public function authorize()\n    {\n        return true;\n    }\n\n    /**\n     * Get the validation rules that apply to the request.\n     *\n     * @return array\n     */\n    public function rules()\n    {\n        return [{$rules}\n        ];\n    }\n}\n";
            file_put_contents($requestPath, $requestContent);
            $this->info("FormRequest $requestName created successfully in folder $folderName.");
        }
    }

    protected function generateUpdateFormRequest($model, array $columns)
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
            $requestContent = "<?php\n\nnamespace App\Http\Requests\\{$folderName};\n\nuse Illuminate\Foundation\Http\FormRequest;\n\nclass {$requestName} extends FormRequest\n{\n    /**\n     * Determine if the user is authorized to make this request.\n     *\n     * @return bool\n     */\n    public function authorize()\n    {\n        return true;\n    }\n\n    /**\n     * Get the validation rules that apply to the request.\n     *\n     * @return array\n     */\n    public function rules()\n    {\n        return [{$rules}\n        ];\n    }\n}\n";
            file_put_contents($requestPath, $requestContent);
            $this->info("FormRequest $requestName created successfully in folder $folderName.");
        }
    }


    protected function generateController($model, array $columns)
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

        $content = "<?php\n\nnamespace App\Http\Controllers;\n\nuse App\Models\\$model;\nuse Illuminate\Http\Request;\nuse CodingPartners\AutoController\Traits\ApiResponseTrait;\nuse CodingPartners\AutoController\Traits\FileStorageTrait;\nuse App\Http\Resources\\{$model}Resource;\nuse App\Http\Requests\\{$model}Request\\Store{$model}Request;\nuse App\Http\Requests\\{$model}Request\\Update{$model}Request;\n";
        $content .= "class {$controllerName} extends Controller\n{\n    use ApiResponseTrait, FileStorageTrait;\n";
        $content .= $this->generateIndexMethod($model);
        $content .= $this->generateStoreMethod($model, $columns);
        $content .= $this->generateShowMethod($model);
        $content .= $this->generateUpdateMethod($model, $columns);
        $content .= $this->generateDestroyMethod($model);
        $content .= "}\n";

        file_put_contents($controllerPath, $content);

        $this->info("Controller $controllerName created successfully.");
    }

    protected function generateIndexMethod($model)
    {
        return "    public function index()\n    {\n        \${$model} = $model::paginate(10);\n        return \$this->resourcePaginated({$model}Resource::collection(\${$model}));\n    }\n\n";
    }

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

        return "    public function store(Store{$model}Request \$request)\n    {\n        \${$model} = {$model}::create([{$assignments}\n        ]);\n        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Created Successfully\", 201);\n    }\n\n";
    }

    protected function generateShowMethod($model)
    {
        return "    public function show($model \${$model})\n    {\n        return \$this->successResponse(new {$model}Resource(\${$model}));\n    }\n\n";
    }

    protected function generateUpdateMethod($model, array $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                if (Str::endsWith($column, '_img')) {
                    $assignments .= "\n        \${$model}->$column = \$this->fileExists(\$request->$column, \${$model}->$column, \"{$model}\") ?? \${$model}->$column;";
                } else {
                    $assignments .= "\n        \${$model}->$column = \$request->$column ?? \${$model}->$column;";
                }
            }
        }

        return "    public function update(Update{$model}Request \$request, $model \${$model})\n    {\n       $assignments\n        \${$model}->save();\n        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Updated Successfully\", 200);\n    }\n\n";
    }

    protected function generateDestroyMethod($model)
    {
        return "    public function destroy($model \${$model})\n    {\n        \${$model}->delete();\n        return \$this->successResponse();\n    }\n\n";
    }

    protected function generateRoutes($model)
    {
        $routesContent = "
    /**
     * {$model} Management Routes
     *
     * These routes handle {$model} management operations.
     */
    Route::apiResource('{$model}s', App\Http\Controllers\\{$model}Controller::class);
            ";

            file_put_contents(base_path('routes/api.php'), $routesContent, FILE_APPEND);
            $this->info("$model Route added successfully.");
        }
    }

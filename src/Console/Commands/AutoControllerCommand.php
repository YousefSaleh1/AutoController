<?php

namespace CodingPartners\AutoController\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutoControllerCommand extends Command
{
    protected $signature = 'crud:generate {model}';
    protected $description = 'Generate CRUD operations for a model';

    /**
     * Handles the main logic of the command.
     *
     * This method is responsible for generating CRUD operations for a given model.
     * It first checks if the specified table exists in the database. If not, it displays an error message and returns.
     * Then, it retrieves the columns of the table and proceeds to generate the necessary files for CRUD operations.
     *
     * @return void
     */
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
        $this->generateStoreFormRequest($model, $columns);

        $this->info("Generating update FormRequest for $model...");
        $this->generateUpdateFormRequest($model, $columns);

        $this->info("Generating CRUD for $model...");
        $this->generateController($model, $columns);

        $this->info("Generating Resource for $model...");
        $this->generateResource($model, $columns);

        $this->info("Generating routes/api.php for $model...");
        $this->generateRoutes($model);
    }

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
use CodingPartners\AutoController\Traits\ApiResponseTrait;
use CodingPartners\AutoController\Traits\FileStorageTrait;
use App\Http\Resources\\{$model}Resource;
use App\Http\Requests\\{$model}Request\\Store{$model}Request;
use App\Http\Requests\\{$model}Request\\Update{$model}Request;\n";
        $content .= "
class {$controllerName} extends Controller
{
    use ApiResponseTrait, FileStorageTrait;\n\n";
        $content .= $this->generateIndexMethod($model);
        $content .= $this->generateStoreMethod($model, $columns);
        $content .= $this->generateShowMethod($model);
        $content .= $this->generateUpdateMethod($model, $columns);
        $content .= $this->generateDestroyMethod($model, $columns);
        $content .= "}\n";

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
        return "    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        \${$model} = $model::paginate(10);
        return \$this->resourcePaginated({$model}Resource::collection(\${$model}));
    }\n\n";
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

        return "    /**
     * Store a newly created resource in storage.
     */
    public function store(Store{$model}Request \$request)
    {
        \${$model} = {$model}::create([{$assignments}
        ]);
        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Created Successfully\", 201);
    }\n\n";
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
        return "    /**
     * Display the specified resource.
     */
    public function show($model \${$model})
    {
        return \$this->successResponse(new {$model}Resource(\${$model}));
    }\n\n";
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

        return "    /**
     * Update the specified resource in storage.
     */
    public function update(Update{$model}Request \$request, $model \${$model})
    {
        \$fieldInputs = \$request->valedated();
        \$data = {$assignments};
        \${$model}->update(array_filter(\$data));
        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Updated Successfully\", 200);
    }\n\n";
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

        return "    /**
     * Remove the specified resource from storage.
     */
    public function destroy($model \${$model})
    {{$assignments}
        \${$model}->delete();
        return \$this->successResponse(null, \"{$model} Deleted Successfully\");
    }\n\n";
    }

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
        $routesContent = "  /**
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

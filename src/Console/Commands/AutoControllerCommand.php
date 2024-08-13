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

        $this->info("Generating CRUD for $model...");
        $this->generateController($model, $columns);

        $this->info("Generating Resource for $model...");
        $this->generateResource($model, $columns);

        $this->info("Generating routes/api.php for $model...");
        $this->generateRoutes($model);
    }

    protected function generateResource($model, array $columns)
    {
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
                $assignments .= "\n        \"$column\" => \$this->$column,";
            }

            $resourceContent = "<?php\n\nnamespace App\Http\Resources;\n\nuse Illuminate\Http\Resources\Json\JsonResource;\n\nclass {$resourceName} extends JsonResource\n{\n    /**\n     * Transform the resource into an array.\n     *\n     * @param  \Illuminate\Http\Request  \$request\n     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable\n     */\n    public function toArray(\$request)\n    {\n        return [            {$assignments}\n        ];\n    }\n}\n";

            file_put_contents($resourcePath, $resourceContent);
            $this->info("Resource $resourceName created successfully.");
        }
    }

    protected function generateController($model, array $columns)
    {
        $controllerName = $model . 'Controller';
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        $content = "<?php\n\nnamespace App\Http\Controllers;\n\nuse App\Models\\$model;\nuse Illuminate\Http\Request;\nuse CodingPartners\AutoController\Traits\ApiResponseTrait;\nuse CodingPartners\AutoController\Traits\FileStorageTrait;\nuse App\Http\Resources\\{$model}Resource;\n\n";
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
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                if (Str::endsWith($column, '_im')) {
                    $assignments .= "\n        \${$model}->$column = \$this->storeFile(\$request->$column, \"{$model}\");";
                } else {
                    $assignments .= "\n        \${$model}->$column = \$request->$column;";
                }
            }
        }

        return "    public function store(Request \$request)\n    {\n        \${$model} = new $model();$assignments\n        \${$model}->save();\n        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Created Successfully\", 201);\n    }\n\n";
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
                if (Str::endsWith($column, '_im')) {
                    $assignments .= "\n        \${$model}->$column = \$this->fileExists(\$request->$column, \${$model}->$column, \"{$model}\") ?? \${$model}->$column;";
                } else {
                    $assignments .= "\n        \${$model}->$column = \$request->$column ?? \${$model}->$column;";
                }
            }
        }

        return "    public function update(Request \$request, $model \${$model})\n    {\n       $assignments\n        \${$model}->save();\n        return \$this->successResponse(new {$model}Resource(\${$model}), \"{$model} Updated Successfully\", 200);\n    }\n\n";
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

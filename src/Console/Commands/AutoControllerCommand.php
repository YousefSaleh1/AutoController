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

        $this->info("Generating routes/api.php for $model...");
        $this->generateRoutes($model);
    }

    protected function generateController($model, $columns)
    {
        $controllerName = $model . 'Controller';
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        $content = "<?php\n\nnamespace App\Http\Controllers;\n\nuse App\Models\\$model;\nuse Illuminate\Http\Request;\n\n";
        $content .= "class {$controllerName} extends Controller\n{\n";
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
        return "    public function index()\n    {\n        \${$model} = $model::all();\n        return response()->json(\${$model});\n    }\n\n";
    }

    protected function generateStoreMethod($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                $assignments .= "\n        \${$model}->$column = \$request->$column;";
            }
        }

        return "    public function store(Request \$request)\n    {\n       \${$model} = new $model();$assignments\n        \${$model}->save();\n        return response()->json(\${$model}, 201);\n    }\n\n";
    }

    protected function generateShowMethod($model)
    {
        return "    public function show($model \${$model})\n    {\n        return response()->json(\${$model});\n    }\n\n";
    }

    protected function generateUpdateMethod($model, $columns)
    {
        $assignments = "";
        foreach ($columns as $column) {
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                $assignments .= "\n        \${$model}->$column = \$request->$column;";
            }
        }

        return "    public function update(Request \$request, $model \${$model})\n    {\n       $assignments\n        \${$model}->save();\n        return response()->json(\${$model});\n    }\n\n";
    }

    protected function generateDestroyMethod($model)
    {
        return "    public function destroy($model \${$model})\n    {\n        \${$model}->delete();\n        return response()->json(null, 204);\n    }\n\n";
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
    }
}

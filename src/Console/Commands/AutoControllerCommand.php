<?php

namespace CodingPartners\AutoController\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use CodingPartners\AutoController\Traits\Generates\GenerateRoutes;
use CodingPartners\AutoController\Traits\Generates\GenerateService;
use CodingPartners\AutoController\Traits\Generates\GenerateResource;
use CodingPartners\AutoController\Traits\Generates\GenerateFormRequest;
use CodingPartners\AutoController\Traits\Generates\ControllerWithService;
use CodingPartners\AutoController\Traits\Generates\ControllerWithoutService;

class AutoControllerCommand extends Command
{
    use ControllerWithService ,ControllerWithoutService, GenerateFormRequest, GenerateService, GenerateResource, GenerateRoutes;

    protected $signature = 'crud:generate {model}';
    protected $description = 'Generate CRUD operations for a model';

    /**
     * Handles the main logic of the command.
     *
     * This method is responsible for generating CRUD soperations for a given model.
     * It first checks if the specified table exists in the database. If not, it displays an error message and returns.
     * Then, it retrieves the columns of the table and proceeds to generate the necessary files for CRUD operations.
     *
     * @return void
     */
    public function handle()
    {
        // Retrieve the model name from the command arguments
        $model = $this->argument('model');
        // Convert the model name to a snake_case table name
        $tableName = Str::snake(Str::plural($model));

        // Check if the table exists in the database
        if (!Schema::hasTable($tableName)) {
            $this->error("Table $tableName does not exist.");
            return;
        }

        // Get a list of all the columns in the table
        $columns = Schema::getColumnListing($tableName);

        // Check if the 'deleted_at' column exists in the table
        $softDelete = in_array('deleted_at', $columns);

        $this->generateStoreFormRequest($model, $columns);

        $this->generateUpdateFormRequest($model, $columns);

        $this->generateResource($model, $columns);

        // Ask the user if they want to generate a service file
        $generateService = $this->confirm("Do you want to generate a Service for $model?");

        if ($generateService) {

            $this->generateService($model, $columns, $softDelete);

            $this->generateControllerWithService($model, $columns, $softDelete);
        } else {
            $this->generateControllerWithoutService($model, $columns, $softDelete);
        }

        $this->generateRoutes($model, $softDelete);
    }
}

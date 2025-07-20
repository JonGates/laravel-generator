<?php

namespace InfyOm\Generator\Generators\API;

use InfyOm\Generator\Generators\BaseGenerator;

class APIResourceGenerator extends BaseGenerator
{
    private string $fileName;

    public function __construct()
    {
        parent::__construct();

        $this->path = $this->config->paths->apiResource;
        $this->fileName = $this->config->modelNames->name.'Resource.php';
    }

    public function variables(): array
    {
        return [
            'fields' => implode(','.infy_nl_tab(1, 3), $this->generateResourceFields()),
        ];
    }

    public function generate()
    {
        $templateData = view('laravel-generator::api.resource.resource', $this->variables())->render();

        $filePath = $this->path.$this->fileName;
        
        // Check if file exists and handle overwrite logic
        if (file_exists($filePath)) {
            // Check if --force option is set
            if (isset($this->config->command) && $this->config->command->option('force')) {
                // Force overwrite, continue generation
            } else {
                // Skip generation without prompting
                $this->config->commandInfo($this->fileName.' already exists. Skipping generation.');
                return;
            }
        }

        g_filesystem()->createFile($filePath, $templateData);

        $this->config->commandInfo($this->fileName);
    }

    protected function generateResourceFields(): array
    {
        $resourceFields = [];
        foreach ($this->config->fields as $field) {
            $resourceFields[] = "'".$field->name."' => \$this->".$field->name;
        }

        return $resourceFields;
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->config->commandComment('API Resource file deleted: '.$this->fileName);
        }
    }
}

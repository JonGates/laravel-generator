<?php

namespace InfyOm\Generator\Generators\API;

use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Generators\ModelGenerator;

class APIRequestGenerator extends BaseGenerator
{
    private string $createFileName;

    private string $updateFileName;

    public function __construct()
    {
        parent::__construct();

        $this->path = $this->config->paths->apiRequest;
        $this->createFileName = $this->config->modelNames->name.'CreateAPIRequest.php';
        $this->updateFileName = $this->config->modelNames->name.'UpdateAPIRequest.php';
    }

    public function generate()
    {
        $this->generateCreateRequest();
        $this->generateUpdateRequest();
    }

    protected function generateCreateRequest()
    {
        $templateData = view('laravel-generator::api.request.create', $this->variables())->render();

        $filePath = $this->path.$this->createFileName;
        
        // Check if file exists and handle overwrite logic
        if (file_exists($filePath)) {
            // Check if --force option is set
            if (isset($this->config->command) && $this->config->command->option('force')) {
                // Force overwrite, continue generation
            } else {
                // Skip generation without prompting
                $this->config->commandInfo($this->createFileName.' already exists. Skipping generation.');
                return;
            }
        }

        g_filesystem()->createFile($filePath, $templateData);

        $this->config->commandInfo($this->createFileName);
    }

    protected function generateUpdateRequest()
    {
        $modelGenerator = app(ModelGenerator::class);
        $rules = $modelGenerator->generateUniqueRules();

        $templateData = view('laravel-generator::api.request.update', [
            'uniqueRules' => $rules,
        ])->render();

        $filePath = $this->path.$this->updateFileName;
        
        // Check if file exists and handle overwrite logic
        if (file_exists($filePath)) {
            // Check if --force option is set
            if (isset($this->config->command) && $this->config->command->option('force')) {
                // Force overwrite, continue generation
            } else {
                // Skip generation without prompting
                $this->config->commandInfo($this->updateFileName.' already exists. Skipping generation.');
                return;
            }
        }

        g_filesystem()->createFile($filePath, $templateData);

        $this->config->commandInfo($this->updateFileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->createFileName)) {
            $this->config->commandComment('Create API Request file deleted: '.$this->createFileName);
        }

        if ($this->rollbackFile($this->path, $this->updateFileName)) {
            $this->config->commandComment('Update API Request file deleted: '.$this->updateFileName);
        }
    }
}

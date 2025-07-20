<?php

namespace InfyOm\Generator\Generators;

class RepositoryGenerator extends BaseGenerator
{
    private string $fileName;

    public function __construct()
    {
        parent::__construct();

        $this->path = $this->config->paths->repository;
        $this->fileName = $this->config->modelNames->name.'Repository.php';
    }

    public function variables(): array
    {
        return [
            'fieldSearchable' => $this->getSearchableFields(),
        ];
    }

    public function generate()
    {
        $this->config->commandComment(infy_nl().'Repository created: ');
        
        $templateData = view('laravel-generator::repository.repository', $this->variables())->render();

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

    protected function getSearchableFields()
    {
        $searchables = [];

        foreach ($this->config->fields as $field) {
            if ($field->isSearchable) {
                $searchables[] = "'".$field->name."'";
            }
        }

        return implode(','.infy_nl_tab(1, 2), $searchables);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->config->commandComment('Repository file deleted: '.$this->fileName);
        }
    }
}

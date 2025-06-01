<?php

namespace InfyOm\Generator\Generators;

use Illuminate\Support\Str;

class ServiceGenerator extends BaseGenerator
{
    private string $fileName;

    public function __construct()
    {
        parent::__construct();

        $this->path = $this->config->paths->service;
        $this->fileName = $this->config->modelNames->name.'Service.php';
    }

    public function variables(): array
    {
        return [
            'fieldSearchable' => $this->getSearchableFields(),
        ];
    }

    public function generate()
    {

        $this->config->commandComment(infy_nl().'Service created: ');
        if (file_exists($this->path.$this->fileName)) {
            $this->config->commandInfo($this->fileName.' already exists. It needs to be manually deleted before you can recreate it.');
            return;
        }

        $templateData = view('laravel-generator::service.service', $this->variables())->render();

        g_filesystem()->createFile($this->path.$this->fileName, $templateData);

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
            $this->config->commandComment('Service file deleted: '.$this->fileName);
        }
    }

}
<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Exception;
use Illuminate\Support\Str;
use InfyOm\Generator\Generators\BaseGenerator;

class ControllerGenerator extends BaseGenerator
{
    private string $templateType;

    private string $namespace;

    private string $fileName;
    
    protected $mode = '';
    
    // 保存原始命名空间和路径，避免多次调用时状态污染
    private string $originalNamespace;
    private string $originalPath;

    public function __construct()
    {
        parent::__construct();

        $this->originalPath = $this->config->paths->controller;
        $this->originalNamespace = $this->config->namespaces->controller;
        $this->path = $this->originalPath;
        $this->templateType = config('laravel_generator.templates', 'adminlte-templates');
        $this->fileName = $this->config->modelNames->name.'Controller.php';
    }

    public function generate($mode = '')
    {        
        $this->mode = $mode;
        // 如果有自定义模式，才更新命名空间和路径
        if ($this->mode) {
            $this->config->setMode($this->mode);
            // setMode后重新获取正确的命名空间和路径
            $this->namespace = $this->config->namespaces->controller;
            $this->path = $this->config->paths->controller;
        } else {
            // 没有mode时使用原始值
            $this->namespace = $this->originalNamespace;
            $this->path = $this->originalPath;
        }
        
        $variables = [];

        switch ($this->config->tableType) {
            case 'blade':
                if ($this->config->options->repositoryPattern) {
                    $indexMethodView = 'index_method_repository';
                } else {
                    $indexMethodView = 'index_method';
                }
                $variables['renderType'] = 'paginate(10)';
                break;

            case 'datatables':
                $indexMethodView = 'index_method_datatable';
                $this->generateDataTable();
                break;

            case 'livewire':
                $indexMethodView = 'index_method_livewire';
                $this->generateLivewireTable();
                break;

            default:
                throw new Exception('Invalid Table Type');
        }

        if ($this->config->options->repositoryPattern) {
            $viewName = 'controller_repository';
        } else {
            $viewName = 'controller';
        }

        $variables['indexMethod'] = view('laravel-generator::scaffold.controller.'.$indexMethodView, $variables)
            ->render();

        $templateData = view('laravel-generator::scaffold.controller.'.$viewName, $variables)->render();

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

    protected function generateDataTable()
    {
        $templateData = view('laravel-generator::scaffold.table.datatable', [
            'columns' => implode(','.infy_nl_tab(1, 3), $this->generateDataTableColumns()),
        ])->render();

        $path = $this->config->paths->dataTables;

        $fileName = $this->config->modelNames->name.'DataTable.php';

        g_filesystem()->createFile($path.$fileName, $templateData);

        $this->config->commandComment(infy_nl().'DataTable created: ');
        $this->config->commandInfo($fileName);
    }

    protected function generateLivewireTable()
    {
        $templateData = view('laravel-generator::scaffold.table.livewire', [
            'columns' => implode(','.infy_nl_tab(1, 3), $this->generateLivewireTableColumns()),
        ])->render();

        $path = $this->config->paths->livewireTables;

        $fileName = $this->config->modelNames->plural.'Table.php';

        g_filesystem()->createFile($path.$fileName, $templateData);

        $this->config->commandComment(infy_nl().'LivewireTable created: ');
        $this->config->commandInfo($fileName);
    }

    protected function generateDataTableColumns(): array
    {
        $dataTableColumns = [];
        foreach ($this->config->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }

            $dataTableColumns[] = trim(view(
                $this->templateType.'::templates.scaffold.table.datatable.column',
                $field->variables()
            )->render());
        }

        return $dataTableColumns;
    }

    protected function generateLivewireTableColumns(): array
    {
        $livewireTableColumns = [];

        foreach ($this->config->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }

            $fieldTemplate = 'Column::make("'.$field->getTitle().'", "'.$field->name.'")'.infy_nl();
            $fieldTemplate .= infy_tabs(4).'->sortable()';

            if ($field->isSearchable) {
                $fieldTemplate .= infy_nl().infy_tabs(4).'->searchable()';
            }

            $livewireTableColumns[] = $fieldTemplate;
        }

        return $livewireTableColumns;
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->config->commandComment('Controller file deleted: '.$this->fileName);
        }

        if ($this->config->tableType === 'datatables') {
            if ($this->rollbackFile(
                $this->config->paths->dataTables,
                $this->config->modelNames->name.'DataTable.php'
            )) {
                $this->config->commandComment('DataTable file deleted: '.$this->fileName);
            }
        }

        if ($this->config->tableType === 'livewire') {
            if ($this->rollbackFile(
                $this->config->paths->livewireTables,
                $this->config->modelNames->plural.'Table.php'
            )) {
                $this->config->commandComment('Livewire Table file deleted: '.$this->fileName);
            }
        }
    }
}

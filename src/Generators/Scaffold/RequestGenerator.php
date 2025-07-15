<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Generators\ModelGenerator;

class RequestGenerator extends BaseGenerator
{
    private string $createFileName;

    private string $updateFileName;

    protected $mode = '';
    
    // 保存原始命名空间，避免多次调用时状态污染
    private string $originalNamespace;
    private string $originalPath;
    private string $namespace;

    public function __construct()
    {
        parent::__construct();

        $this->originalPath = $this->config->paths->request;
        $this->originalNamespace = $this->config->namespaces->request;
        $this->path = $this->originalPath;
        $this->namespace = $this->originalNamespace;
        $this->createFileName = $this->config->modelNames->name.'CreateRequest.php';
        $this->updateFileName = $this->config->modelNames->name.'UpdateRequest.php';
    }

    public function generate($mode = '')
    {
        $this->mode = $mode;
        if ($this->mode) {
            $this->config->setMode($this->mode);
            // setMode后重新获取正确的命名空间和路径
            $this->namespace = $this->config->namespaces->request;
            $this->path = $this->config->paths->request;
        }
        $this->generateCreateRequest();
        $this->generateUpdateRequest();
    }

    protected function generateCreateRequest()
    {

        $templateData = view('laravel-generator::scaffold.request.create', $this->variables())->render();

        g_filesystem()->createFile($this->path.$this->createFileName, $templateData);

        $this->config->commandComment(infy_nl().'Create Request created: ');
        $this->config->commandInfo($this->createFileName);
    }

    protected function generateUpdateRequest()
    {
        // 确保使用当前mode设置的路径（在generateCreateRequest中已设置）
        // 这里不需要重置，因为generateCreateRequest已经设置了正确的路径和命名空间
        
        $modelGenerator = new ModelGenerator();
        $rules = $modelGenerator->generateUniqueRules();

        $templateData = view('laravel-generator::scaffold.request.update', [
            'uniqueRules' => $rules,
        ])->render();

        g_filesystem()->createFile($this->path.$this->updateFileName, $templateData);

        $this->config->commandComment(infy_nl().'Update Request created: ');
        $this->config->commandInfo($this->updateFileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->createFileName)) {
            $this->config->commandComment('Create Request file deleted: '.$this->createFileName);
        }

        if ($this->rollbackFile($this->path, $this->updateFileName)) {
            $this->config->commandComment('Update Request file deleted: '.$this->updateFileName);
        }
    }
}

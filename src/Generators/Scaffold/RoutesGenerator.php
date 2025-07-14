<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use InfyOm\Generator\Generators\BaseGenerator;

class RoutesGenerator extends BaseGenerator
{
    protected $mode = '';
    
    // 保存原始路径，避免多次调用时状态污染
    private string $originalPath;
    
    public function __construct()
    {
        parent::__construct();

        $this->originalPath = $this->config->paths->routes;
        $this->path = $this->originalPath;
    }

    public function generate($mode = '')
    {
        $this->mode = $mode;
        
        // 每次生成前重置为原始状态，避免状态污染
        $this->path = $this->originalPath;
        
        if ($this->mode) {
            // 根据mode选择不同的路由文件
            $routeFileName = strtolower($this->mode) . '.php';
            $this->path = dirname($this->originalPath) . '/' . $routeFileName;

            $this->config->prefixes->route = $this->mode;
            $this->config->prefixes->view = $this->mode;
        }
        
        $routeContents = g_filesystem()->getFile($this->path);

        $routes = view('laravel-generator::scaffold.routes')->render();

        if (Str::contains($routeContents, $routes)) {
            $this->config->commandInfo(infy_nl().'Route '.$this->config->modelNames->dashedPlural.' already exists, Skipping Adjustment.');

            return;
        }

        $routeContents .= infy_nl().$routes;

        g_filesystem()->createFile($this->path, $routeContents);
        $this->config->commandComment(infy_nl().$this->config->modelNames->dashedPlural.' routes added.');
    }

    public function rollback()
    {
        $routeContents = g_filesystem()->getFile($this->path);

        $routes = view('laravel-generator::scaffold.routes')->render();

        if (Str::contains($routeContents, $routes)) {
            $routeContents = str_replace($routes, '', $routeContents);
            g_filesystem()->createFile($this->path, $routeContents);
            $this->config->commandComment('scaffold routes deleted');
        }
    }
}

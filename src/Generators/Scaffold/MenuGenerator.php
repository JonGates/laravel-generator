<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use InfyOm\Generator\Generators\BaseGenerator;

class MenuGenerator extends BaseGenerator
{
    private string $templateType;
    
    protected $mode = '';
    
    // 保存原始路径，避免多次调用时状态污染
    private string $originalPath;

    public function __construct()
    {
        parent::__construct();

        $this->originalPath = config('laravel_generator.path.menu_file', resource_path('views/layouts/menu.blade.php'));
        $this->path = $this->originalPath;
        $this->templateType = config('laravel_generator.templates', 'adminlte-templates');
    }

    public function generate($mode = '')
    {
        $this->mode = $mode;
        
        // 每次生成前重置为原始状态，避免状态污染
        $this->path = $this->originalPath;
        
        if ($this->mode) {
            // 根据mode选择不同的菜单文件
            $menuFileName = strtolower($this->mode) . '_menu.blade.php';
            $this->path = dirname($this->originalPath) . '/' . $menuFileName;

            $this->config->prefixes->route = $this->mode;
            $this->config->prefixes->view = $this->mode;

        }
        
        $menuContents = g_filesystem()->getFile($this->path);

        $menu = view($this->templateType.'::templates.layouts.menu_template')->render();

        if (Str::contains($menuContents, $menu)) {
            $this->config->commandInfo(infy_nl().'Menu '.$this->config->modelNames->humanPlural.' already exists, Skipping Adjustment.');

            return;
        }

        $menuContents .= infy_nl().$menu;

        g_filesystem()->createFile($this->path, $menuContents);
        $this->config->commandComment(infy_nl().$this->config->modelNames->dashedPlural.' menu added.');
    }

    public function rollback()
    {
        $menuContents = g_filesystem()->getFile($this->path);

        $menu = view($this->templateType.'::templates.layouts.menu_template')->render();

        if (Str::contains($menuContents, $menu)) {
            g_filesystem()->createFile($this->path, str_replace($menu, '', $menuContents));
            $this->config->commandComment('menu deleted');
        }
    }
}

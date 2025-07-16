<?php

namespace InfyOm\Generator\Common;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InfyOm\Generator\DTOs\GeneratorNamespaces;
use InfyOm\Generator\DTOs\GeneratorOptions;
use InfyOm\Generator\DTOs\GeneratorPaths;
use InfyOm\Generator\DTOs\GeneratorPrefixes;
use InfyOm\Generator\DTOs\ModelNames;

class GeneratorConfig
{
    public GeneratorNamespaces $namespaces;
    public GeneratorPaths $paths;
    public ModelNames $modelNames;
    public GeneratorPrefixes $prefixes;
    public GeneratorOptions $options;
    public Command $command;

    /** @var GeneratorField[] */
    public array $fields = [];

    /** @var GeneratorFieldRelation[] */
    public array $relations = [];

    protected static $dynamicVars = [];

    public $tableName;
    public string $tableType;
    public string $apiPrefix;
    public $primaryName;
    public $connection;
    
    // 添加mode属性来支持不同的生成模式（web/admin）
    public string $mode = '';

    public function init()
    {
        $this->loadModelNames();
        $this->loadPrefixes();
        $this->loadPaths();
        $this->tableType = config('laravel_generator.tables', 'blade');
        $this->apiPrefix = config('laravel_generator.api_prefix', 'api');
        $this->loadNamespaces();
        $this->prepareTable();
        $this->prepareOptions();
    }

    public static function addDynamicVariable(string $name, $value)
    {
        self::$dynamicVars[$name] = $value;
    }

    public static function addDynamicVariables(array $vars)
    {
        foreach ($vars as $key => $value) {
            self::addDynamicVariable($key, $value);
        }
    }

    public function getDynamicVariable(string $name)
    {
        return self::$dynamicVars[$name];
    }

    public function setCommand(Command &$command)
    {
        $this->command = &$command;
    }

    /**
     * 设置生成模式
     *
     * @param string $mode 生成模式（web/admin等）
     */
    public function setMode(string $mode)
    {
        $this->mode = $mode;
        
        // 当设置mode时，重新加载模型名称、前缀、路径和命名空间
        if (!empty($mode)) {
            // 先重新加载模型名称，因为buildRoutePrefix依赖于modelNames->namespace
            $this->loadModelNames();
            $this->loadPrefixes();
            $this->loadNamespaces();
            $this->loadPaths();
        }
    }


    /**
     * 格式化模型名称
     *
     * @param string $name 模型名称
     * @return array 包含命名空间和模型名称的数组 ['namespace' => '命名空间', 'model' => '模型名称']
     * 
     * 示例：
     * hello\\englishWordLessonCard  => ['namespace' => 'Hello', 'model' => 'EnglishWordLessonCard']
     * hello.englishWordLessonCard  => ['namespace' => 'Hello', 'model' => 'EnglishWordLessonCard']
     * hello/EnglishWordLessonCard  => ['namespace' => 'Hello', 'model' => 'EnglishWordLessonCard']
     * ABC/hello/EnglishWordLessonCard  => ['namespace' => 'Abc\\Hello', 'model' => 'EnglishWordLessonCard']
     * abc\\hello/EnglishWordLessonCard  => ['namespace' => 'Abc\\Hello', 'model' => 'EnglishWordLessonCard']
     * EnglishWordLessonCard => ['namespace' => '', 'model' => 'EnglishWordLessonCard']
     */
    public function formatModelName(string $name): array
    {
        // 如果输入为空，返回空的命名空间和模型名称
        if (empty($name)) {
            return ['namespace' => '', 'model' => ''];
        }

        // 统一分隔符：将所有可能的分隔符（\\、.、/）替换为统一的分隔符
        // 注意：需要先处理双反斜杠，避免被单反斜杠处理覆盖
        $normalizedName = str_replace(['\\\\', '\\', '.', '/'], '|', $name);
        
        // 按分隔符拆分成数组
        $parts = explode('|', $normalizedName);
        
        // 过滤掉空字符串部分
        $parts = array_filter($parts, function($part) {
            return !empty(trim($part));
        });
        
        // 如果没有有效部分，返回空结果
        if (empty($parts)) {
            return ['namespace' => '', 'model' => ''];
        }
        
        // 格式化每个部分
        $formattedParts = array_map(function($part) {
            $part = trim($part);
            
            // 如果部分为空，跳过
            if (empty($part)) {
                return '';
            }
            
            // 处理驼峰命名：先转换为StudlyCase（首字母大写的驼峰）
            // 这会处理像 "englishWordLessonCard" 这样的输入
            $studlyPart = \Illuminate\Support\Str::studly($part);
            
            // 如果原始部分全是大写（如ABC），则只首字母大写，其余小写
            if (ctype_upper($part) && strlen($part) > 1) {
                return ucfirst(strtolower($part));
            }
            
            return $studlyPart;
        }, $parts);
        
        // 过滤掉空的格式化部分
        $formattedParts = array_filter($formattedParts, function($part) {
            return !empty($part);
        });
        
        // 重新索引数组
        $formattedParts = array_values($formattedParts);
        
        // 分离命名空间和模型名称
        if (count($formattedParts) === 1) {
            // 只有一个部分，作为模型名称，命名空间为空
            return [
                'namespace' => '',
                'model' => $formattedParts[0]
            ];
        } else {
            // 多个部分，最后一个作为模型名称，其余作为命名空间
            $modelName = array_pop($formattedParts);
            $namespace = implode('\\', $formattedParts);
            
            return [
                'namespace' => $namespace,
                'model' => $modelName
            ];
        }
    }


    public function loadModelNames()
    {
        $modelNames = new ModelNames();
        $modelNames->name = $this->command->argument('model');
        
        // 格式化模型名称，获取命名空间和模型名称
        $formattedModel = $this->formatModelName($modelNames->name);
        $modelNames->name = $formattedModel['model'];
        
        // 保存命名空间信息供后续使用
        $modelNames->namespace = $formattedModel['namespace'];

        if ($this->getOption('plural')) {
            $modelNames->plural = $this->getOption('plural');
        } else {
            $modelNames->plural = Str::plural($modelNames->name);
        }

        // 将模型名称的单数形式转换为小驼峰式（首字母小写）
        $modelNames->camel = Str::camel($modelNames->name);
        // 示例： "user_role" → "userRole"

        // 将模型名称的复数形式转换为小驼峰式
        $modelNames->camelPlural = Str::camel($modelNames->plural);
        // 示例： "user_roles" → "userRoles"

        // 将模型名称的单数形式转换为蛇形命名（小写下划线）
        $modelNames->snake = Str::snake($modelNames->name);
        // 示例： "UserRole" → "user_role"

        // 将模型名称的复数形式转换为蛇形命名
        $modelNames->snakePlural = Str::snake($modelNames->plural);
        // 示例： "UserRoles" → "user_roles"

        // 将模型名称的单数形式转换为短横线命名（kebab-case）
        $modelNames->dashed = Str::kebab($modelNames->name);
        // 示例： "userRole" → "user-role"

        // 将模型名称的复数形式转换为短横线命名
        $modelNames->dashedPlural = Str::kebab($modelNames->plural);
        // 示例： "userRoles" → "user-roles"

        // 生成单数形式的人类可读名称（空格分隔+首字母大写）
        $modelNames->human = Str::title(str_replace('_', ' ', $modelNames->snake));
        // 示例： "user_role" → "User Role"

        // 生成复数形式的人类可读名称
        $modelNames->humanPlural = Str::title(str_replace('_', ' ', $modelNames->snakePlural));
        // 示例： "user_roles" → "User Roles"

        $this->modelNames = $modelNames;
    }

    public function loadPrefixes()
    {
        $prefixes = new GeneratorPrefixes();

        $prefixes->route = config('laravel_generator.prefixes.route', '');
        $prefixes->namespace = config('laravel_generator.prefixes.namespace', '');
        $prefixes->view = config('laravel_generator.prefixes.view', '');

        if ($this->getOption('prefix')) {
            $multiplePrefixes = explode('/', $this->getOption('prefix'));

            $prefixes->mergeRoutePrefix($multiplePrefixes);
            $prefixes->mergeNamespacePrefix($multiplePrefixes);
            $prefixes->mergeViewPrefix($multiplePrefixes);
        }

        // 构建完整的路由前缀，包含mode和完整的命名空间信息
        $this->buildRoutePrefix($prefixes);

        $this->prefixes = $prefixes;
    }

    /**
     * 构建路由前缀，包含mode和完整的命名空间信息
     * 格式：基础前缀.mode.命名空间（用点分隔）
     *
     * @param GeneratorPrefixes $prefixes 前缀对象
     * @return void
     */
    private function buildRoutePrefix(GeneratorPrefixes $prefixes): void
    {
        $routeParts = [];
        
        // 添加基础路由前缀（如果存在）
        if (!empty($prefixes->route)) {
            $routeParts[] = $prefixes->route;
        }
        
        // 添加mode（如果存在）
        if (!empty($this->mode)) {
            $routeParts[] = strtolower($this->mode);
        }
        
        // 添加完整的模型命名空间（如果存在）
        if (isset($this->modelNames) && !empty($this->modelNames->namespace)) {
            $modelNamespace = $this->modelNames->namespace;
            
            // 将命名空间转换为小写并用点分隔，处理多层命名空间
            $namespaceForRoute = strtolower(str_replace('\\', '.', $modelNamespace));
            $routeParts[] = $namespaceForRoute;
        }
        
        // 重新组合路由前缀
        $prefixes->route = implode('.', $routeParts);
    }

    public function loadPaths()
    {
        $paths = new GeneratorPaths();
    
        $namespacePrefix = $this->prefixes->namespace;
        $viewPrefix = $this->prefixes->view;
    
        // 构建完整的命名空间前缀：mode + modelNamespace 
        $fullNamespacePrefix = $this->buildNamespacePrefix($namespacePrefix);
        
        // 构建公用组件的路径前缀（不包含mode，只包含模型命名空间）
        $sharedPathPrefix = $this->buildSharedPathPrefix($namespacePrefix);
        
        if (!empty($viewPrefix)) {
            $viewPrefix .= '/';
        }
    
        // Model、Service、Repository是公用的，不需要mode
        $paths->repository = config(
            'laravel_generator.path.repository',
            app_path('Repositories/')
        ).$sharedPathPrefix;
    
        $paths->model = config('laravel_generator.path.model', app_path('Models/')).$sharedPathPrefix;
    
        $paths->dataTables = config(
            'laravel_generator.path.datatables',
            app_path('DataTables/')
        ).$sharedPathPrefix;
    
        $paths->livewireTables = config(
            'laravel_generator.path.livewire_tables',
            app_path('Http/Livewire/')
        );
    
        $paths->apiController = config(
            'laravel_generator.path.api_controller',
            app_path('Http/Controllers/API/')
        ).$sharedPathPrefix;
    
        $paths->apiResource = config(
            'laravel_generator.path.api_resource',
            app_path('Http/Resources/API/')
        ).$sharedPathPrefix;
    
        $paths->apiRequest = config(
            'laravel_generator.path.api_request',
            app_path('Http/Requests/API/')
        ).$sharedPathPrefix;
    
        $paths->apiRoutes = config(
            'laravel_generator.path.api_routes',
            base_path('routes/api.php')
        );
    
        $paths->apiTests = config('laravel_generator.path.api_test', base_path('tests/API/')).$sharedPathPrefix;
    
        $paths->controller = config(
            'laravel_generator.path.controller',
            app_path('Http/Controllers/')
        ).$fullNamespacePrefix;

        $paths->request = config('laravel_generator.path.request', app_path('Http/Requests/')).$fullNamespacePrefix;

        // Add this line after the request path assignment
        $paths->service = config(
            'laravel_generator.path.service',
            app_path('Services/')
        ).$sharedPathPrefix;

        // 根据mode动态选择路由文件
        $routeFile = 'web.php'; // 默认路由文件
        if (!empty($this->mode)) {
            $routeFile = strtolower($this->mode) . '.php';
        }
        $paths->routes = config('laravel_generator.path.routes', base_path('routes/' . $routeFile));
        $paths->factory = config('laravel_generator.path.factory', database_path('factories/'));

        $paths->views = config(
            'laravel_generator.path.views',
            resource_path('views/')
        ).strtolower($fullNamespacePrefix).$this->modelNames->snakePlural.'/';
 
        $paths->seeder = config('laravel_generator.path.seeder', database_path('seeders/'));
        $paths->databaseSeeder = config('laravel_generator.path.database_seeder', database_path('seeders/DatabaseSeeder.php'));
        $paths->viewProvider = config(
            'laravel_generator.path.view_provider',
            app_path('Providers/ViewServiceProvider.php')
        );

        $paths->tests = config('laravel_generator.path.tests', base_path('tests/'));
        $paths->repositoryTests = config(
            'laravel_generator.path.repository_tests',
            base_path('tests/Repositories/')
        );
        $paths->apiTests = config('laravel_generator.path.api_tests', base_path('tests/APIs/'));
    
        $this->paths = $paths;
    }

    /**
     * 构建公用组件的路径前缀（使用斜杠分隔符）
     * 格式：prefixes + modelNamespace + '/'
     * 注意：不包含mode，用于Model、Service、Repository等公用组件
     *
     * @param string $namespacePrefix 基础命名空间前缀
     * @return string 公用组件的路径前缀
     */
    public function buildSharedPathPrefix(string $namespacePrefix): string
    {
        $parts = [];
        
        // 添加基础前缀
        if (!empty($namespacePrefix)) {
            $parts[] = $namespacePrefix;
        }
        
        // 只添加模型命名空间（如果存在），不添加mode
        if (isset($this->modelNames) && !empty($this->modelNames->namespace)) {
            // 将反斜杠转换为斜杠
            $modelNamespace = str_replace('\\', '/', $this->modelNames->namespace);
            $parts[] = $modelNamespace;
        }
        
        // 组合所有部分并添加尾部斜杠
        $result = implode('/', $parts);
        return !empty($result) ? $result . '/' : '';
    }

    /**
     * 构建完整的命名空间前缀
     * 格式：prefixes + mode + modelNamespace + '/'
     * 注意：如果模型命名空间已经包含mode，则不重复添加
     *
     * @param string $namespacePrefix 基础命名空间前缀
     * @return string 完整的命名空间前缀
     */
    public function buildNamespacePrefix(string $namespacePrefix): string
    {
        $parts = [];
        
        // 添加基础前缀
        if (!empty($namespacePrefix)) {
            $parts[] = $namespacePrefix;
        }
        
        // 检查模型命名空间是否已经包含mode
        $modelNamespace = '';
        $shouldAddMode = true;
        
        if (isset($this->modelNames) && !empty($this->modelNames->namespace)) {
            $modelNamespace = $this->modelNames->namespace;
            
            // 如果模型命名空间以mode结尾，则不重复添加mode
            if (!empty($this->mode)) {
                $modeUcfirst = ucfirst($this->mode);
                $namespaceParts = explode('\\', $modelNamespace);
                $lastPart = end($namespaceParts);
                
                if ($lastPart === $modeUcfirst) {
                    $shouldAddMode = false;
                }
            }
        }
        
        // 添加mode（如果需要且存在）
        if (!empty($this->mode) && $shouldAddMode) {
            $parts[] = ucfirst($this->mode);
        }
        
        // 添加模型命名空间（如果存在）
        if (!empty($modelNamespace)) {
            // 将反斜杠转换为斜杠
            $modelNamespace = str_replace('\\', '/', $modelNamespace);
            $parts[] = $modelNamespace;
        }
        
        // 组合所有部分并添加尾部斜杠
        $result = implode('/', $parts);
        return !empty($result) ? $result . '/' : '';
    }

    public function loadNamespaces()
    {
        $prefix = $this->prefixes->namespace;
        
        // 构建完整的命名空间前缀（使用反斜杠分隔符）
        $fullNamespacePrefix = $this->buildFullNamespacePrefix($prefix);
        
        // 构建公用组件的命名空间前缀（不包含mode，只包含模型命名空间）
        $sharedNamespacePrefix = $this->buildSharedNamespacePrefix($prefix);

        $namespaces = new GeneratorNamespaces();

        $namespaces->app = app()->getNamespace();
        $namespaces->app = substr($namespaces->app, 0, strlen($namespaces->app) - 1);
        // Model、Service、Repository是公用的，不需要mode
        $namespaces->repository = config('laravel_generator.namespace.repository', 'App\Repositories').$sharedNamespacePrefix;
        $namespaces->model = config('laravel_generator.namespace.model', 'App\Models').$sharedNamespacePrefix;
        $namespaces->seeder = config('laravel_generator.namespace.seeder', 'Database\Seeders').$sharedNamespacePrefix;
        $namespaces->service = config('laravel_generator.namespace.service', 'App\Service').$sharedNamespacePrefix;
        $namespaces->factory = config('laravel_generator.namespace.factory', 'Database\Factories').$fullNamespacePrefix;
        $namespaces->dataTables = config('laravel_generator.namespace.datatables', 'App\DataTables').$fullNamespacePrefix;
        $namespaces->livewireTables = config('laravel_generator.namespace.livewire_tables', 'App\Http\Livewire');
        $namespaces->modelExtend = config(
            'laravel_generator.model_extend_class',
            'Illuminate\Database\Eloquent\Model'
        );

        $namespaces->apiController = config(
            'laravel_generator.namespace.api_controller',
            'App\Http\Controllers\API'
        ).$fullNamespacePrefix;
        $namespaces->apiResource = config(
            'laravel_generator.namespace.api_resource',
            'App\Http\Resources'
        ).$fullNamespacePrefix;

        $namespaces->apiRequest = config(
            'laravel_generator.namespace.api_request',
            'App\Http\Requests\API'
        ).$fullNamespacePrefix;

        $namespaces->request = config(
            'laravel_generator.namespace.request',
            'App\Http\Requests'
        ).$fullNamespacePrefix;
        $namespaces->requestBase = config('laravel_generator.namespace.request', 'App\Http\Requests');
        $namespaces->baseController = config('laravel_generator.namespace.controller', 'App\Http\Controllers');
        $namespaces->controller = config(
            'laravel_generator.namespace.controller',
            'App\Http\Controllers'
        ).$fullNamespacePrefix;

        $namespaces->apiTests = config('laravel_generator.namespace.api_test', 'Tests\APIs');
        $namespaces->repositoryTests = config('laravel_generator.namespace.repository_test', 'Tests\Repositories');
        $namespaces->tests = config('laravel_generator.namespace.tests', 'Tests');

        $this->namespaces = $namespaces;
    }

    /**
     * 构建公用组件的命名空间前缀（使用反斜杠分隔符）
     * 格式：prefixes + modelNamespace + '\\'
     * 注意：不包含mode，用于Model、Service、Repository等公用组件
     *
     * @param string $namespacePrefix 基础命名空间前缀
     * @return string 公用组件的命名空间前缀
     */
    public function buildSharedNamespacePrefix(string $namespacePrefix): string
    {
        $parts = [];
        
        // 添加基础前缀
        if (!empty($namespacePrefix)) {
            $parts[] = $namespacePrefix;
        }
        
        // 只添加模型命名空间（如果存在），不添加mode
        if (isset($this->modelNames) && !empty($this->modelNames->namespace)) {
            $parts[] = $this->modelNames->namespace;
        }
        
        // 组合所有部分并添加前导反斜杠
        $result = implode('\\', $parts);
        return !empty($result) ? '\\' . $result : '';
    }

    /**
     * 构建完整的命名空间前缀（使用反斜杠分隔符）
     * 格式：prefixes + mode + modelNamespace + '\\'
     * 注意：如果模型命名空间已经包含mode，则不重复添加
     *
     * @param string $namespacePrefix 基础命名空间前缀
     * @return string 完整的命名空间前缀
     */
    public function buildFullNamespacePrefix(string $namespacePrefix): string
    {
        $parts = [];
        
        // 添加基础前缀
        if (!empty($namespacePrefix)) {
            $parts[] = $namespacePrefix;
        }
        
        // 检查模型命名空间是否已经包含mode
        $modelNamespace = '';
        $shouldAddMode = true;
        
        if (isset($this->modelNames) && !empty($this->modelNames->namespace)) {
            $modelNamespace = $this->modelNames->namespace;
            
            // 如果模型命名空间以mode结尾，则不重复添加mode
            if (!empty($this->mode)) {
                $modeUcfirst = ucfirst($this->mode);
                $namespaceParts = explode('\\', $modelNamespace);
                $lastPart = end($namespaceParts);
                
                if ($lastPart === $modeUcfirst) {
                    $shouldAddMode = false;
                }
            }
        }
        
        // 添加mode（如果需要且存在）
        if (!empty($this->mode) && $shouldAddMode) {
            $parts[] = ucfirst($this->mode);
        }

        // 添加模型命名空间（如果存在）
        if (!empty($modelNamespace)) {
            $parts[] = $modelNamespace;
        }
        
        // 组合所有部分并添加前导反斜杠
        $result = implode('\\', $parts);
        return !empty($result) ? '\\' . $result : '';
    }

    public function prepareTable()
    {
        if ($this->getOption('table')) {
            $this->tableName = $this->getOption('table');
        } else {
            $this->tableName = $this->modelNames->snakePlural;
        }

        if ($this->getOption('primary')) {
            $this->primaryName = $this->getOption('primary');
        } else {
            $this->primaryName = 'id';
        }

        if ($this->getOption('connection')) {
            $this->connection = $this->getOption('connection');
        }
    }

    public function prepareOptions()
    {
        $options = new GeneratorOptions();

        $options->softDelete = config('laravel_generator.options.soft_delete', false);
        $options->saveSchemaFile = config('laravel_generator.options.save_schema_file', true);
        $options->localized = config('laravel_generator.options.localized', false);
        $options->repositoryPattern = config('laravel_generator.options.repository_pattern', true);
        $options->resources = config('laravel_generator.options.resources', false);
        $options->factory = config('laravel_generator.options.factory', false);
        $options->seeder = config('laravel_generator.options.seeder', false);
        $options->swagger = config('laravel_generator.options.swagger', false);
        $options->tests = config('laravel_generator.options.tests', false);
        $options->excludedFields = config('laravel_generator.options.excluded_fields', ['id']);
$this->options = $options;
    }

    public function overrideOptionsFromJsonFile($jsonData)
    {
//        $options = self::$availableOptions;
//
//        foreach ($options as $option) {
//            if (isset($jsonData['options'][$option])) {
//                $this->setOption($option, $jsonData['options'][$option]);
//            }
//        }
//
//        // prepare prefixes than reload namespaces, paths and dynamic variables
//        if (!empty($this->getOption('prefix'))) {
//            $this->preparePrefixes();
//            $this->loadPaths();
//            $this->loadNamespaces();
//            $this->loadDynamicVariables();
//        }
//
//        $addOns = ['swagger', 'tests', 'datatables'];
//
//        foreach ($addOns as $addOn) {
//            if (isset($jsonData['addOns'][$addOn])) {
//                $this->addOns[$addOn] = $jsonData['addOns'][$addOn];
//            }
//        }
    }

    public function getOption($option)
    {
        return $this->command->option($option);
    }

    public function commandError($error)
    {
        $this->command->error($error);
    }

    public function commandComment($message)
    {
        $this->command->comment($message);
    }

    public function commandWarn($warning)
    {
        $this->command->warn($warning);
    }

    public function commandInfo($message)
    {
        $this->command->info($message);
    }
}

@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $namespaceApp }}Repositories;

// 引入Laravel容器类，用于依赖注入
use Illuminate\Container\Container as Application;
// 引入分页接口，用于数据分页
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
// 引入查询构建器，用于构建数据库查询
use Illuminate\Database\Eloquent\Builder;
// 引入集合类，用于处理模型集合
use Illuminate\Database\Eloquent\Collection;
// 引入Eloquent模型基类
use Illuminate\Database\Eloquent\Model;
// 引入请求类，用于处理HTTP请求
use Illuminate\Http\Request;

// 抽象基础仓库类，所有具体仓库都继承此类
abstract class BaseRepository
{
    /**
     * @var Model
     * 受保护的模型实例属性
     */
    protected $model;

    /**
     * 构造函数
     * @throws \Exception 如果模型创建失败则抛出异常
     */
    public function __construct()
    {
        // 调用makeModel方法初始化模型实例
        $this->makeModel();
    }

    /**
     * 获取可搜索字段数组的抽象方法
     * 子类必须实现此方法来定义哪些字段可以被搜索
     */
    abstract public function getFieldsSearchable(): array;

    /**
     * 配置模型的抽象方法
     * 子类必须实现此方法来指定使用的模型类
     */
    abstract public function model(): string;

    /**
     * 创建模型实例
     *
     * @throws \Exception 如果模型类不是Model的实例则抛出异常
     *
     * @return Model 返回模型实例
     */
    public function makeModel()
    {
        // 通过Laravel容器创建模型实例
        $model = app($this->model());

        // 检查创建的实例是否为Model类型
        if (!$model instanceof Model) {
            // 如果不是Model实例，抛出异常
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        // 将创建的模型实例赋值给属性并返回
        return $this->model = $model;
    }

    /**
     * 为脚手架分页记录
     * @param int $perPage 每页显示的记录数
     * @param array $columns 要查询的字段，默认为所有字段
     * @return LengthAwarePaginator 返回分页器实例
     */
    public function paginate(int $perPage, array $columns = ['*']): LengthAwarePaginator
    {
        // 获取查询构建器
        $query = $this->allQuery();

        // 执行分页查询并返回分页器
        return $query->paginate($perPage, $columns);
    }

    /**
     * 构建用于检索所有记录的查询
     * @param array $search 搜索条件数组
     * @param int $skip 跳过的记录数
     * @param int $limit 限制返回的记录数
     * @return Builder 返回查询构建器
     */
    public function allQuery(array $search = [], int $skip = null, int $limit = null): Builder
    {
        // 创建新的查询构建器实例
        $query = $this->model->newQuery();

        // 如果有搜索条件
        if (count($search)) {
            // 遍历搜索条件
            foreach($search as $key => $value) {
                // 检查字段是否在可搜索字段列表中
                if (in_array($key, $this->getFieldsSearchable())) {
                    // 添加where条件到查询
                    $query->where($key, $value);
                }
            }
        }

        // 如果设置了跳过记录数
        if (!is_null($skip)) {
            // 添加skip条件
            $query->skip($skip);
        }

        // 如果设置了限制记录数
        if (!is_null($limit)) {
            // 添加limit条件
            $query->limit($limit);
        }

        // 返回构建好的查询
        return $query;
    }

    /**
     * 根据给定的过滤条件检索所有记录
     * @param array $search 搜索条件
     * @param int $skip 跳过的记录数
     * @param int $limit 限制记录数
     * @param array $columns 要查询的字段
     * @return Collection 返回模型集合
     */
    public function all(array $search = [], int $skip = null, int $limit = null, array $columns = ['*']): Collection
    {
        // 获取查询构建器
        $query = $this->allQuery($search, $skip, $limit);

        // 执行查询并返回集合
        return $query->get($columns);
    }

    /**
     * 创建模型记录
     * @param array $input 要创建的数据数组
     * @return Model 返回创建的模型实例
     */
    public function create(array $input): Model
    {
        // 创建新的模型实例并填充数据
        $model = $this->model->newInstance($input);

        // 保存模型到数据库
        $model->save();

        // 返回保存后的模型实例
        return $model;
    }

    /**
     * 根据给定ID查找模型记录
     * @param int $id 要查找的记录ID
     * @param array $columns 要查询的字段
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find(int $id, array $columns = ['*'])
    {
        // 创建新的查询构建器
        $query = $this->model->newQuery();

        // 根据ID查找记录并返回
        return $query->find($id, $columns);
    }

    /**
     * 根据给定ID更新模型记录
     * @param array $input 要更新的数据
     * @param int $id 要更新的记录ID
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update(array $input, int $id)
    {
        // 创建新的查询构建器
        $query = $this->model->newQuery();

        // 根据ID查找记录，如果不存在则抛出异常
        $model = $query->findOrFail($id);

        // 用新数据填充模型
        $model->fill($input);

        // 保存更新后的模型
        $model->save();

        // 返回更新后的模型实例
        return $model;
    }

    /**
     * 删除指定ID的记录
     * @param int $id 要删除的记录ID
     * @throws \Exception 如果删除失败则抛出异常
     * @return bool|mixed|null 返回删除结果
     */
    public function delete(int $id)
    {
        // 创建新的查询构建器
        $query = $this->model->newQuery();

        // 根据ID查找记录，如果不存在则抛出异常
        $model = $query->findOrFail($id);

        // 删除模型记录并返回结果
        return $model->delete();
    }



    /**
     * 应用搜索条件到查询构建器
     *
     * @param Builder $query 原始查询构建器
     * @param Request $request
     * @return Builder 应用搜索条件后的查询构建器
     * 
     * @example
     * 【GET】
     * URL 普通格式：?name=测试&status=1&category[]=1&category[]=2&created_at=2025-01-01
     * URL 高级格式：?query[]=name|text|测试&query[]=status|number|1|1&query[]=category|checkbox|1,2
     * 
     * 【POST】
     * JSON搜索条件格式：{"query": ["field1|number|1|5", "field2|checkbox|1,2,3,4|", "field3|date|2025-01-01|2025-02-01", "field4|text|你好|", ...]}
     * 
     * 搜索类型type：
     * - text：模糊搜索
     * - number：数字范围搜索
     * - date：日期范围搜索
     * - checkbox：多选搜索
     *
     */
    public function applySearchConditions(Builder $query, Request $request): Builder
    {
        // 统一的搜索条件数组
        $searchQueries = [];
        
        // 处理JSON搜索条件 - 转换为统一格式
        if ($request->isJson()) {
            $jsonData = $request->json()->all();
            if (isset($jsonData['query']) && is_array($jsonData['query'])) {
                $searchQueries = array_merge($searchQueries, $jsonData['query']);
            }
        }
        
        // 处理高级搜索 - 直接使用
        if ($request->has('query') && is_array($request->input('query'))) {
            $searchQueries = array_merge($searchQueries, $request->input('query'));
        }
        
        // 处理普通搜索 - 转换为统一格式
        $this->convertSimpleSearchToAdvanced($request, $searchQueries);
        
        // 统一处理所有搜索条件
        foreach ($searchQueries as $queryString) {
            $this->applySingleSearchCondition($query, $queryString);
        }
        
        return $query;
    }

    /**
     * 将普通搜索参数转换为高级搜索格式
     *
     * @param Request $request
     * @param array &$searchQueries 搜索条件数组（引用传递）
     */
    private function convertSimpleSearchToAdvanced(Request $request, array &$searchQueries): void
    {
        // 获取所有请求参数
        $allParams = $request->all();
        
        // 排除系统参数
        $excludeParams = ['page', 'per_page', 'query', 'search', 'field', 'sort', 'sort_type', 'limit', 'skip'];
        
        foreach ($allParams as $fieldName => $fieldValue) {
            // 跳过系统参数和空值
            if (in_array($fieldName, $excludeParams) || empty($fieldValue)) {
                continue;
            }
            
            // 根据字段类型确定搜索类型
            $searchType = $this->determineFieldSearchType($fieldName);
            
            // 转换为高级搜索格式
            if ($searchType === 'checkbox' && is_array($fieldValue)) {
                // 多选字段：field|checkbox|value1,value2,value3|
                $values = implode(',', array_filter($fieldValue));
                if (!empty($values)) {
                    $searchQueries[] = "{$fieldName}|checkbox|{$values}||";
                }
            } elseif ($searchType === 'number' && is_numeric($fieldValue)) {
                // 数字字段：精确匹配转换为范围搜索
                $searchQueries[] = "{$fieldName}|number|{$fieldValue}|{$fieldValue}";
            } elseif ($searchType === 'date' && !empty($fieldValue)) {
                // 日期字段：精确匹配
                $searchQueries[] = "{$fieldName}|date|{$fieldValue}|{$fieldValue}";
            } elseif ($searchType === 'text' && !empty($fieldValue)) {
                // 文本字段：模糊搜索
                $searchQueries[] = "{$fieldName}|text|{$fieldValue}||";
            }
        }
    }

    /**
     * 确定字段的搜索类型
     *
     * @param string $fieldName
     * @return string
     */
    abstract public function determineFieldSearchType(string $fieldName): string;

    /**
     * 应用单个搜索条件
     *
     * @param Builder $query
     * @param string $queryString
     */
    private function applySingleSearchCondition(Builder $query, string $queryString): void
    {
        $parts = explode('|', $queryString);
        if (count($parts) < 3) return;
        
        $field = $parts[0];
        $type = $parts[1];
        
        switch ($type) {
            case 'text':
                if (count($parts) >= 3 && !empty($parts[2])) {
                    $value = implode('|', array_slice($parts, 2));
                    $query->where($field, 'LIKE', "%{$value}%");
                }
                break;
                
            case 'number':
                if (count($parts) >= 4) {
                    $min = !empty($parts[2]) ? $parts[2] : null;
                    $max = !empty($parts[3]) ? $parts[3] : null;
                    
                    if ($min !== null) {
                        $query->where($field, '>=', $min);
                    }
                    if ($max !== null) {
                        $query->where($field, '<=', $max);
                    }
                }
                break;
                
            case 'date':
                if (count($parts) >= 4) {
                    $startDate = !empty($parts[2]) ? $parts[2] : null;
                    $endDate = !empty($parts[3]) ? $parts[3] : null;
                    
                    if ($startDate !== null) {
                        $query->whereDate($field, '>=', $startDate);
                    }
                    if ($endDate !== null) {
                        $query->whereDate($field, '<=', $endDate);
                    }
                }
                break;
                
            case 'checkbox':
                if (count($parts) >= 3 && !empty($parts[2])) {
                    $values = explode(',', $parts[2]);
                    $values = array_filter($values, function($value) {
                        return $value !== '';
                    });
                    
                    if (!empty($values)) {
                        $query->whereIn($field, $values);
                    }
                }
                break;
        }
    }




}

@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $namespaceApp }}Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Get searchable fields array
     */
    abstract public function getFieldsSearchable(): array;

    /**
     * Configure the Model
     */
    abstract public function model(): string;

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel()
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Paginate records for scaffold.
     */
    public function paginate(int $perPage, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->allQuery();

        return $query->paginate($perPage, $columns);
    }

    /**
     * Build a query for retrieving all records.
     */
    public function allQuery(array $search = [], int $skip = null, int $limit = null): Builder
    {
        $query = $this->model->newQuery();

        if (count($search)) {
            foreach($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable())) {
                    $query->where($key, $value);
                }
            }
        }

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Retrieve all records with given filter criteria
     */
    public function all(array $search = [], int $skip = null, int $limit = null, array $columns = ['*']): Collection
    {
        $query = $this->allQuery($search, $skip, $limit);

        return $query->get($columns);
    }

    /**
     * Create model record
     */
    public function create(array $input): Model
    {
        $model = $this->model->newInstance($input);

        $model->save();

        return $model;
    }

    /**
     * Find model record for given id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find(int $id, array $columns = ['*'])
    {
        $query = $this->model->newQuery();

        return $query->find($id, $columns);
    }

    /**
     * Update model record for given id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update(array $input, int $id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        $model->fill($input);

        $model->save();

        return $model;
    }

    /**
     * @throws \Exception
     *
     * @return bool|mixed|null
     */
    public function delete(int $id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

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
        $excludeParams = ['page', 'per_page', 'query', 'search', 'field'];
        
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

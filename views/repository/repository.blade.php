@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $config->namespaces->repository }};

use {{ $config->namespaces->model }}\{{ $config->modelNames->name }};
use {{ $config->namespaces->app }}\Repositories\BaseRepository;

class {{ $config->modelNames->name }}Repository extends BaseRepository
{
    /**
     * 允许搜索的字段
     * @var array
     */
    protected $fieldSearchable = [
        {!! $fieldSearchable !!}
    ];

    /**
     * 字段搜索的类型
     * @var array
     */
    protected $fieldSearchType = [
@php
    $numericFields = [];
    $dateFields = [];
    $checkboxFields = [];
    $textFields = [];
    
    foreach($config->fields as $fieldItem) {
        if (in_array($fieldItem->htmlType, ['number'])) {
            $numericFields[] = "'$fieldItem->name'";
        }
        if (in_array($fieldItem->htmlType, ['date'])) {
            $dateFields[] = "'$fieldItem->name'";
        }
        if (in_array($fieldItem->htmlType, ['checkbox', 'boolean', 'select', 'radio'])) {
            $checkboxFields[] = "'$fieldItem->name'";
        }
        if (in_array($fieldItem->htmlType, ['text', 'textarea', 'email', 'url'])) {
            $textFields[] = "'$fieldItem->name'";
        }
    }
@endphp
@if(count($numericFields) > 0)
        'numeric' => [{!! implode(', ', $numericFields) !!}],
@endif
@if(count($dateFields) > 0)
        'date' => [{!! implode(', ', $dateFields) !!}],
@endif
@if(count($checkboxFields) > 0)
        'checkbox' => [{!! implode(', ', $checkboxFields) !!}],
@endif
@if(count($textFields) > 0)
        'text' => [{!! implode(', ', $textFields) !!}]
@endif
    ];

    /**
     * 获取字段搜索的类型
     * @param string|null $type
     * @return array
     */
    public function getFieldsSearchType($type = null): array
    {
        if ($type) {
            return $this->fieldSearchType[$type] ?? [];
        }
        return $this->fieldSearchType;
    }

    /**
     * 获取允许搜索的字段
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * 配置模型
     * @return string
     */
    public function model(): string
    {
        return {{ $config->modelNames->name }}::class;
    }

    
    /**
     * 确定字段的搜索类型
     *
     * @param string $fieldName
     * @return string
     */
    public function determineFieldSearchType(string $fieldName): string
    {
        // 检查字段是否在数字字段列表中
        if (in_array($fieldName, $this->getFieldsSearchType('numeric'))) {
            return 'number';
        }
        
        // 检查字段是否在日期字段列表中
        if (in_array($fieldName, $this->getFieldsSearchType('date'))) {
            return 'date';
        }
        
        // 检查字段是否在多选字段列表中
        if (in_array($fieldName, $this->getFieldsSearchType('checkbox'))) {
            return 'checkbox';
        }
        
        // 默认为文本搜索
        return 'text';
    }
}

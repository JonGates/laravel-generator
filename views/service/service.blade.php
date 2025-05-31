@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $config->namespaces->service }};

use {{ $config->namespaces->model }}\{{ $config->modelNames->name }};
use {{ $config->namespaces->repository }}\{{ $config->modelNames->name }}Repository;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * {{ $config->modelNames->name }}Service
 * 
 * 处理{{ $config->modelNames->human }}相关的业务逻辑
 * 本类不会被自动生成覆盖，可以将非自动生成的业务逻辑放在这里，方便以后维护。
 * 
 */
class {{ $config->modelNames->name }}Service
{
    protected {{ $config->modelNames->name }} ${{ $config->modelNames->camel }};
    protected {{ $config->modelNames->name }}Repository ${{ $config->modelNames->camel }}Repository;

    public function __construct({{ $config->modelNames->name }}Repository ${{ $config->modelNames->camel }}Repository, {{ $config->modelNames->name }} ${{ $config->modelNames->camel }})
    {
        $this->{{ $config->modelNames->camel }} = ${{ $config->modelNames->camel }};
        $this->{{ $config->modelNames->camel }}Repository = ${{ $config->modelNames->camel }}Repository;
    }
    



}
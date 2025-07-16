@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $config->namespaces->apiController }};

use {{ $config->namespaces->apiRequest }}\{{ $config->modelNames->name }}CreateAPIRequest;
use {{ $config->namespaces->apiRequest }}\{{ $config->modelNames->name }}UpdateAPIRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use {{ $config->namespaces->app }}\Http\Controllers\AppBaseController;
use {{ $config->namespaces->model }}\{{ $config->modelNames->name }};
use {{ $config->namespaces->service }}\{{ $config->modelNames->name }}Service;
use {{ $config->namespaces->apiResource }}\{{ $config->modelNames->name }}Resource;
use {{ $config->namespaces->repository }}\{{ $config->modelNames->name }}Repository;

{!! $docController !!}
class {{ $config->modelNames->name }}APIController extends AppBaseController
{
    private {{ $config->modelNames->name }}Service ${{ $config->modelNames->camel }}Service;
    private {{ $config->modelNames->name }}Repository ${{ $config->modelNames->camel }}Repository;

    public function __construct({{ $config->modelNames->name }}Repository ${{ $config->modelNames->camel }}Repository, {{ $config->modelNames->name }}Service ${{ $config->modelNames->camel }}Service)
    {
        $this->{{ $config->modelNames->camel }}Service = ${{ $config->modelNames->camel }}Service;
        $this->{{ $config->modelNames->camel }}Repository = ${{ $config->modelNames->camel }}Repository;
        
        // 中间件
        // $this->middleware('permission:view-{{ $config->modelNames->camel }}')->only(['index', 'show']);
        // $this->middleware('permission:create-{{ $config->modelNames->camel }}')->only(['create', 'store']);
        // $this->middleware('permission:edit-{{ $config->modelNames->camel }}')->only(['edit', 'update']);
        // $this->middleware('permission:delete-{{ $config->modelNames->camel }}')->only(['destroy']);
    }

    {!! $docIndex !!}
    public function index(Request $request): JsonResponse
    {
       
        $query = $this->{{ $config->modelNames->camel }}Repository->allQuery();

        // 处理搜索条件
        $query = $this->{{ $config->modelNames->camel }}Repository->applySearchConditions($query, $request);

        // 其他查询条件，如排序等
        
        // 分页处理
        $perPage = $request->get('limit', 10);
        ${{ $config->modelNames->camelPlural }} = $query->paginate($perPage)->appends(request()->except('page'));
    
@if($config->options->localized)
        return $this->sendResponse(
            {{ $config->modelNames->name }}Resource::collection(${{ $config->modelNames->camelPlural }}),
            __('messages.retrieved', ['model' => __('models/{{ $config->modelNames->camelPlural }}.plural')])
        );
@else
        return $this->sendResponse({{ $config->modelNames->name }}Resource::collection(${{ $config->modelNames->camelPlural }}), '{{ $config->modelNames->humanPlural }} retrieved successfully');
@endif
    }

    {!! $docStore !!}
    public function store({{ $config->modelNames->name }}CreateAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::create($input);

@if($config->options->localized)
        return $this->sendResponse(
            new {{ $config->modelNames->name }}Resource(${{ $config->modelNames->camel }}),
            __('messages.saved', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendResponse(new {{ $config->modelNames->name }}Resource(${{ $config->modelNames->camel }}), '{{ $config->modelNames->human }} saved successfully');
@endif
    }

    {!! $docShow !!}
    public function show($id): JsonResponse
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        if (empty(${{ $config->modelNames->camel }})) {
@if($config->options->localized)
            return $this->sendError(
                __('messages.not_found', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
            );
@else
            return $this->sendError('{{ $config->modelNames->human }} not found');
@endif
        }

@if($config->options->localized)
        return $this->sendResponse(
            new {{ $config->modelNames->name }}Resource(${{ $config->modelNames->camel }}),
            __('messages.retrieved', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendResponse(new {{ $config->modelNames->name }}Resource(${{ $config->modelNames->camel }}), '{{ $config->modelNames->human }} retrieved successfully');
@endif
    }

    {!! $docUpdate !!}
    public function update($id, {{ $config->modelNames->name }}UpdateAPIRequest $request): JsonResponse
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        if (empty(${{ $config->modelNames->camel }})) {
@if($config->options->localized)
        return $this->sendError(
            __('messages.not_found', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
            return $this->sendError('{{ $config->modelNames->human }} not found');
@endif
        }

        ${{ $config->modelNames->camel }}->fill($request->all());
        ${{ $config->modelNames->camel }}->save();

@if($config->options->localized)
        return $this->sendResponse(
            new {{ $config->modelNames->name }}Resource(${{ $config->modelNames->camel }}),
            __('messages.updated', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendResponse(new {{ $config->modelNames->name }}Resource(${{ $config->modelNames->camel }}), '{{ $config->modelNames->name }} updated successfully');
@endif
    }

    {!! $docDestroy !!}
    public function destroy($id): JsonResponse
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        if (empty(${{ $config->modelNames->camel }})) {
@if($config->options->localized)
            return $this->sendError(
                __('messages.not_found', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
            );
@else
            return $this->sendError('{{ $config->modelNames->human }} not found');
@endif
        }

        ${{ $config->modelNames->camel }}->delete();

@if($config->options->localized)
        return $this->sendResponse(
            $id,
            __('messages.deleted', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendSuccess('{{ $config->modelNames->human }} deleted successfully');
@endif
    }
}

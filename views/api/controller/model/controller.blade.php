@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $config->namespaces->apiController }};

use {{ $config->namespaces->apiRequest }}\{{ $config->modelNames->name }}CreateAPIRequest;
use {{ $config->namespaces->apiRequest }}\{{ $config->modelNames->name }}UpdateAPIRequest;
use {{ $config->namespaces->model }}\{{ $config->modelNames->name }};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use {{ $config->namespaces->app }}\Http\Controllers\AppBaseController;
use {{ $config->namespaces->service }}\{{ $config->modelNames->name }}Service;
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
        $query = {{ $config->modelNames->name }}::query();

        if ($request->get('skip')) {
            $query->skip($request->get('skip'));
        }
        if ($request->get('limit')) {
            $query->limit($request->get('limit'));
        }

        ${{ $config->modelNames->camelPlural }} = $query->get();

@if($config->options->localized)
        return $this->sendResponse(
            ${{ $config->modelNames->camelPlural }}->toArray(),
            __('messages.retrieved', ['model' => __('models/{{ $config->modelNames->camelPlural }}.plural')])
        );
@else
        return $this->sendResponse(${{ $config->modelNames->camelPlural }}->toArray(), '{{ $config->modelNames->humanPlural }} retrieved successfully');
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
            ${{ $config->modelNames->camel }}->toArray(),
            __('messages.saved', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendResponse(${{ $config->modelNames->camel }}->toArray(), '{{ $config->modelNames->human }} saved successfully');
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
            ${{ $config->modelNames->camel }}->toArray(),
            __('messages.retrieved', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendResponse(${{ $config->modelNames->camel }}->toArray(), '{{ $config->modelNames->human }} retrieved successfully');
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
            ${{ $config->modelNames->camel }}->toArray(),
            __('messages.updated', ['model' => __('models/{{ $config->modelNames->camelPlural }}.singular')])
        );
@else
        return $this->sendResponse(${{ $config->modelNames->camel }}->toArray(), '{{ $config->modelNames->name }} updated successfully');
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

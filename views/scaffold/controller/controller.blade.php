@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $config->namespaces->controller }};

@if(config('laravel_generator.tables') == 'datatables')
use {{ $config->namespaces->dataTables }}\{{ $config->modelNames->name }}DataTable;
@endif
use {{ $config->namespaces->request }}\{{ $config->modelNames->name }}CreateRequest;
use {{ $config->namespaces->request }}\{{ $config->modelNames->name }}UpdateRequest;
use {{ $config->namespaces->app }}\Http\Controllers\AppBaseController;
use {{ $config->namespaces->service }}\{{ $config->modelNames->name }}Service;
use {{ $config->namespaces->repository }}\{{ $config->modelNames->name }}Repository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

class {{ $config->modelNames->name }}Controller extends AppBaseController
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


    /**
     * Display a listing of the {{ $config->modelNames->name }}.
     */
    {!! $indexMethod !!}

    /**
     * Show the form for creating a new {{ $config->modelNames->name }}.
     */
    public function create()
    {
        return view('{{ $config->prefixes->getViewPrefixForInclude() }}{{ $config->modelNames->snakePlural }}.create');
    }

    /**
     * Store a newly created {{ $config->modelNames->name }} in storage.
     */
    public function store({{ $config->modelNames->name }}CreateRequest $request)
    {
        $input = $request->all();

        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::create($input);

        @include('laravel-generator::scaffold.controller.messages.save_success')

        return redirect(route('{{ $config->prefixes->getRoutePrefixWith('.') }}{{ $config->modelNames->camelPlural }}.index'));
    }

    /**
     * Display the specified {{ $config->modelNames->name }}.
     */
    public function show($id)
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        @include('laravel-generator::scaffold.controller.messages.not_found')

        return view('{{ $config->prefixes->getViewPrefixForInclude() }}{{ $config->modelNames->snakePlural }}.show')->with('{{ $config->modelNames->camel }}', ${{ $config->modelNames->camel }});
    }

    /**
     * Show the form for editing the specified {{ $config->modelNames->name }}.
     */
    public function edit($id)
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        @include('laravel-generator::scaffold.controller.messages.not_found')

        return view('{{ $config->prefixes->getViewPrefixForInclude() }}{{ $config->modelNames->snakePlural }}.edit')->with('{{ $config->modelNames->camel }}', ${{ $config->modelNames->camel }});
    }

    /**
     * Update the specified {{ $config->modelNames->name }} in storage.
     */
    public function update($id, {{ $config->modelNames->name }}UpdateRequest $request)
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        @include('laravel-generator::scaffold.controller.messages.not_found')

        ${{ $config->modelNames->camel }}->fill($request->all());
        ${{ $config->modelNames->camel }}->save();

        @include('laravel-generator::scaffold.controller.messages.update_success')

        return redirect(route('{{ $config->prefixes->getRoutePrefixWith('.') }}{{ $config->modelNames->camelPlural }}.index'));
    }

    /**
     * Remove the specified {{ $config->modelNames->name }} from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        /** @var {{ $config->modelNames->name }} ${{ $config->modelNames->camel }} */
        ${{ $config->modelNames->camel }} = {{ $config->modelNames->name }}::find($id);

        @include('laravel-generator::scaffold.controller.messages.not_found')

        ${{ $config->modelNames->camel }}->delete();

        @include('laravel-generator::scaffold.controller.messages.delete_success')

        return redirect(route('{{ $config->prefixes->getRoutePrefixWith('.') }}{{ $config->modelNames->camelPlural }}.index'));
    }
}

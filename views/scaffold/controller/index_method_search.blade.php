    public function index(Request $request)
    {
        $query = $this->{{ $config->modelNames->camel }}Repository->allQuery();
        
        // 处理搜索条件
        if ($request->has('search') && $request->filled('search')) {
            $field = $request->input('field', 'name');
            $searchTerm = $request->input('search');
            
            // 根据字段类型确定搜索方式
            if (in_array($field, ['id', @foreach($config->fields as $field)@if($field->isNumeric()) '{{ $field->name }}',@endif @endforeach])) {
                // 数字字段精确匹配
                $query->where($field, $searchTerm);
            } else {
                // 文本字段模糊匹配
                $query->where($field, 'LIKE', "%{$searchTerm}%");
            }
        }
        
        ${{ $config->modelNames->camelPlural }} = $query->paginate(10);

        return view('{{ $config->prefixes->getViewPrefixForInclude() }}{{ $config->modelNames->snakePlural }}.index')
            ->with('{{ $config->modelNames->camelPlural }}', ${{ $config->modelNames->camelPlural }});
    } 
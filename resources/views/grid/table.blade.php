<div class="dcat-content {!!  $grid->treePanelStatus() ? 'row' :'' !!}">
    @if($grid->treePanelStatus() && $grid->getTreePanel())
        <div class="dcat-box dcat-tree-con col-md-2" style="padding: 0px;">
            {!! $grid->getTreePanel()->render() !!}
        </div>
    @endif
    <div class="dcat-box dcat-grid-con {!!  $grid->treePanelStatus() ? 'col-md-10' :'' !!}">

        <div class="d-block pb-0">
            @include('admin::grid.table-toolbar')
        </div>
    
        {!! $grid->renderFilter() !!}
    
        {!! $grid->renderHeader() !!}
    
        <div class="{!! $grid->formatTableParentClass() !!}" style="{{$grid->getTableWrapperStyle()}}">
            <table class="{{ $grid->formatTableClass() }}" id="{{ $tableId }}" >
                <thead>
                @if ($headers = $grid->getVisibleComplexHeaders())
                    <tr>
                        @foreach($headers as $header)
                            {!! $header->render() !!}
                        @endforeach
                    </tr>
                @endif
                <tr>
                    @foreach($grid->getVisibleColumns() as $column)
                        <th {!! $column->formatTitleAttributes() !!}>{!! $column->getLabel() !!}{!! $column->renderHeader() !!}</th>
                    @endforeach
                </tr>
                </thead>
    
                @if ($grid->hasQuickCreate())
                    {!! $grid->renderQuickCreate() !!}
                @endif
    
                <tbody>
                {{--@foreach($grid->rows() as $row)
                    <tr {!! $row->rowAttributes() !!}>
                        @foreach($grid->getVisibleColumnNames() as $name)
                            <td {!! $row->columnAttributes($name) !!}>{!! $row->column($name) !!}</td>
                        @endforeach
                    </tr>
                @endforeach--}}
    
                @php $mergerow_arr = []; $field = ''; @endphp
                @foreach($grid->rows() as $row)
                    @foreach($grid->getVisibleColumnNames() as $name)
                        @if(!empty($row->columnAttributesArr($name)['mergeRows']))
                            @php
                                if(!empty($mergerow_arr[$row->column($name)])){
                                    $mergerow_arr[$row->column($name)] = $mergerow_arr[$row->column($name)] + 1;
                                }else{
                                    $mergerow_arr[$row->column($name)] = 1;
                                }
    
                            @endphp
                        @endif
                    @endforeach
                @endforeach
    
                @foreach($grid->rows() as $row)
                    <tr {!! $row->rowAttributes() !!} @if($grid->allowColumnLink()) onclick="window.location.href = '{{$grid->resource()}}/{{$row->id}}'" @endif>
    
                        @foreach($grid->getVisibleColumnNames() as $name)
                            @if(!empty($mergerow_arr[$row->column($name)]))
                                <td style="text-align: center; vertical-align: middle;"
                                    rowspan="{{$mergerow_arr[$row->column($name)]}}">{!! $row->column($name) !!}</td>
                                @php unset($mergerow_arr[$row->column($name)]);$mergerow_arr[$row->column($name).'_use'] = 1 @endphp
    
                            @else
                                @if(!empty($mergerow_arr[$row->column($name).'_use']))
                                @else
                                    <td {!! $row->columnAttributes($name) !!}>{!! $row->column($name) !!}</td>
                                @endif
                            @endif
    
                        @endforeach
                    </tr>
                @endforeach
    
                {{-- 表格汇总--}}
                {{--@if($grid->getSummarizerStatus())
                    <tr class="table-active">
                        <td colspan="{!! count($grid->getVisibleColumnNames()) !!}">汇总</td>
                        @foreach($grid->getVisibleColumns() as $column)
                            @if(in_array($column->getName(),['__row_selector__','id']))
                                @continue;
                            @endif
                            <td>{!! $column->getLabel() !!}</td>
                        @endforeach
                    </tr>
                    @if($grid->getSummarizerThisPageStatus())
                        <tr>
    
                            <td colspan="2"><b>当前页</b></td>
                            <td>123</td>
                            <td>
                                <div><b>平均值:</b> 123</div>
                                <div><b>总和:</b> 456</div>
                                <div><b>总计:</b> 789</div>
                                <div><b>范围:</b> 123-890</div>
                            </td>
                            <td>123</td>
                            <td>123</td>
                            <td>123</td>
                            <td></td>
    
                        </tr>
                    @endif
                    @if($grid->getSummarizerAllPageStatus())
                        <tr class="bg-info">
                            <td></td>
                            <td></td>
                            <td><b>全部</b></td>
                            <td>123</td>
                            <td>123</td>
                            <td>123</td>
                            <td>123</td>
                            <td></td>
                        </tr>
                    @endif
                @endif --}}
    
                @if ($grid->rows()->isEmpty())
                    <tr>
                        <td colspan="{!! count($grid->getVisibleColumnNames()) !!}">
                            <div style="margin:5px 0 0 10px;"><span class="help-block" style="margin-bottom:0"><i class="feather icon-alert-circle"></i>&nbsp;{{ trans('admin.no_data') }}</span></div>
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    
        {!! $grid->renderFooter() !!}
    
        {!! $grid->renderPagination() !!}
    
    </div>
</div>

@if($grid->treePanelStatus() && $grid->getTreePanel())
<script>
(function() {
    // 等待 DOM 加载完成
    Dcat.ready(function () {
        var treePanel = $('.dcat-tree-con');
        if (!treePanel.length) return;

        // 获取当前选中的分类ID（从URL参数）
        var urlParams = new URLSearchParams(window.location.search);
        var currentCatId = urlParams.get('cat_id');

        // 树节点点击事件处理
        treePanel.on('click', '.dd-item, .dd-handle, .dd-content', function(e) {
            e.stopPropagation();
            
            // 查找最近的树项
            var $item = $(this).closest('.dd-item');
            if (!$item.length) return;

            // 获取树项的 data-id 属性
            var catId = $item.attr('data-id');
            if (!catId) {
                // 尝试从子元素中获取ID
                var $branch = $item.find('.dd-content');
                var branchText = $branch.text().trim();
                // 尝试从文本中提取ID（格式可能是 "ID - Title"）
                var match = branchText.match(/^(\d+)\s*-/);
                if (match) {
                    catId = match[1];
                }
            }

            if (!catId) return;

            // 移除其他项的选中状态
            treePanel.find('.dd-item').removeClass('tree-node-active');
            // 添加当前项的选中状态
            $item.addClass('tree-node-active');

            // 构建新的URL
            var url = new URL(window.location.href);
            if (catId) {
                url.searchParams.set('cat_id', catId);
            } else {
                url.searchParams.delete('cat_id');
            }
            // 重置分页
            url.searchParams.delete('_page');

            // 跳转到新URL（这会触发表格刷新）
            window.location.href = url.toString();
        });

        // 高亮当前选中的节点
        if (currentCatId) {
            treePanel.find('.dd-item').each(function() {
                var $item = $(this);
                var itemId = $item.attr('data-id');
                if (!itemId) {
                    var $branch = $item.find('.dd-content');
                    var branchText = $branch.text().trim();
                    var match = branchText.match(/^(\d+)\s*-/);
                    if (match) {
                        itemId = match[1];
                    }
                }
                if (itemId == currentCatId) {
                    $item.addClass('tree-node-active');
                }
            });
        }

        // 添加样式
        if (!$('#tree-panel-styles').length) {
            $('<style id="tree-panel-styles">')
                .text(`
                    .dcat-tree-con .dd-item.tree-node-active {
                        background-color: #e3f2fd;
                    }
                    .dcat-tree-con .dd-item.tree-node-active .dd-content {
                        color: #1976d2;
                        font-weight: bold;
                    }
                    .dcat-tree-con .dd-item {
                        cursor: pointer;
                        border-radius: 4px;
                        margin: 2px 0;
                    }
                    .dcat-tree-con .dd-item:hover {
                        background-color: #f5f5f5;
                    }
                `)
                .appendTo('head');
        }
    });
})();
</script>
@endif

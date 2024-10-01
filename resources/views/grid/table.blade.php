
<div class="dcat-box">

    <div class="d-block pb-0">
        @include('admin::grid.table-toolbar')
    </div>

    {!! $grid->renderFilter() !!}

    {!! $grid->renderHeader() !!}

    <div class="{!! $grid->formatTableParentClass() !!}">
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

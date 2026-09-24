@once
<style>
    .has-many-table-box fieldset {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px 10px;
        margin: 5px 0 10px;
    }
    .has-many-table-box legend {
        font-weight: bold;
        padding: 0 5px;
    }
    .has-many-table-box .fields-group {
        background-color: #eff1f780;
        margin-bottom: 5px;
        border-bottom: 3px solid #ffffff;
    }
    .table-has-many .input-group { flex-wrap: nowrap !important; }

    /* 收紧栅格留白：外层与单元格内 row 负边距、col 内边距、form-group 下边距清零 */
    .has-many-table-box .row { margin-left: 0; margin-right: 0; }
    .has-many-table-box .col-md-12 { padding-left: 0; padding-right: 0; }
    .has-many-table-box .form-group { margin-bottom: 0; }

    /* 单元格紧凑：垂直居中 + 四向内边距收窄 */
    .table-has-many > thead > tr > th,
    .table-has-many > tbody > tr > td {
        vertical-align: middle;
        padding: .5rem;
    }

    /* 表头内容居中 */
    .table-has-many > thead > tr > th { text-align: center; }

    /* switch/number 列（td.text-center）单元格内容居中：
       字段外层是 flex 行（form-group.row）与弹性输入组（input-group），需逐层放开对齐 */
    .table-has-many > tbody > tr > td.text-center .row { justify-content: center; }
    .table-has-many > tbody > tr > td.text-center .input-group { justify-content: center; }

    /* 删除按钮列收窄 */
    .table-has-many .table-remove-col { width: 46px; text-align: center; }
    .table-has-many .table-remove-col .remove { float: none; }

    /* number 微调按钮（-/+）在表格内隐藏，收窄列宽 */
    .table-has-many .number-group .input-group-btn { display: none; }

    /* useTable 模式下去掉 select2 选中项的省略号 */
    .table-has-many .select2-container .select2-selection--single .select2-selection__rendered {
        text-overflow: clip;
    }

    /* 表头字段 help 提示图标（提示框由底部脚本用 layer.tips 定位渲染） */
    .table-has-many th .table-th-tip {
        color: #b3b9bf;
        cursor: help;
        margin-left: 2px;
    }
</style>
@endonce
<div class="has-many-table-box">
    <fieldset>
        <legend class="w-auto">{!! $label !!}</legend>
        <div class="has-many-table-content">
            <div class="row form-group">
                <div class="col-md-12">
                    @include('admin::form.error')

                    <span name="{{$column}}"></span> {{-- 用于显示错误信息 --}}

                    <div class="has-many-table-{{$columnClass}}" >
                        <table class="table table-has-many has-many-table-{{$columnClass}}">
                            <thead>
                            <tr>
                                @foreach($tableHeaders as $header)
                                    <th @if($header['width']) style="width: {!! $header['width'] !!}" @endif @if($header['class']) class="{{ $header['class'] }}" @endif>
                                        {!! $header['label'] !!}
                                        @if($header['help'])
                                            <i class="feather icon-help-circle table-th-tip" data-title="{{ $header['help'] }}"></i>
                                        @endif
                                    </th>
                                @endforeach

                                <th class="hidden"></th>

                                @if($options['allowDelete'])
                                    <th class="table-remove-col"></th>
                                @endif
                            </tr>
                            </thead>
                            <tbody class="has-many-table-{{$columnClass}}-forms">
                            @foreach($forms as $pk => $form)
                                <tr class="has-many-table-{{$columnClass}}-form fields-group">

                                    <?php $hidden = ''; ?>

                                    @foreach($form->fields() as $fi => $field)

                                        @if (is_a($field, Dcat\Admin\Form\Field\Hidden::class))
                                            <?php $hidden .= $field->render(); ?>
                                            @continue
                                        @endif

                                        <td class="{{ $tdClasses[$fi] ?? '' }}">{!! $field->render() !!}</td>
                                    @endforeach

                                    <td class="hidden">{!! $hidden !!}</td>

                                    @if($options['allowDelete'])
                                        <td class="table-remove-col">
                                            <div class="remove btn btn-white btn-sm"><i class="feather icon-trash"></i></div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <template class="{{$columnClass}}-tpl">
                            <tr class="has-many-table-{{$columnClass}}-form fields-group">

                                {!! $template !!}

                                <td class="table-remove-col">
                                    <div class="remove btn btn-white btn-sm"><i class="feather icon-trash"></i></div>
                                </td>
                            </tr>
                        </template>

                        @if($options['allowCreate'])
                            <div class="form-group row m-t-10">
                                <div class="{{$viewClass['field']}}" style="margin-top: 8px">
                                    <div class="add btn btn-primary btn-outline btn-sm"><i class="feather icon-plus"></i>&nbsp;{{ trans('admin.new') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
</div>


{{--<hr style="margin-top: 0px;">--}}

<script>
(function () {
    var nestedIndex = {!! $count !!},
        container = '.has-many-table-{{ $columnClass }}';

    function replaceNestedFormIndex(value) {
        return String(value).replace(/{{ $parentKey ?: Dcat\Admin\Form\NestedForm::DEFAULT_KEY_NAME }}/g, nestedIndex);
    }

    $(document).off('click', container+' .add').on('click', container+' .add', function (e) {
        var $con = $(this).closest(container);
        var tpl = $con.find('template.{{ $columnClass }}-tpl');

        nestedIndex++;

        $con.find('.has-many-table-{{ $columnClass }}-forms').append(replaceNestedFormIndex(tpl.html()));

        e.preventDefault();
        return false
    });

    $(document).off('click', container+' .remove').on('click', container+' .remove', function () {
        var $form = $(this).closest('.has-many-table-{{ $columnClass }}-form');

        $form.hide();
        $form.find('[required]').prop('required', false);
        $form.find('.{{ Dcat\Admin\Form\NestedForm::REMOVE_FLAG_CLASS }}').val(1);
    });

    // 表头 help 提示：用 layer.tips（JS 定位、挂在 body 上，不参与表格布局，
    // 避免纯 CSS 伪元素方案因 hover 丢失/撑出滚动条导致的闪烁）
    $(document).off('mouseover', container+' .table-th-tip').on('mouseover', container+' .table-th-tip', function () {
        if ($(this).attr('layer-idx')) {
            return;
        }
        var idx = layer.tips($(this).data('title'), this, {
            tips: [3, '#2b3541'], // 3 = 显示在下方
            time: 0,
            maxWidth: 280
        });
        $(this).attr('layer-idx', idx);
    }).off('mouseleave', container+' .table-th-tip').on('mouseleave', container+' .table-th-tip', function () {
        layer.close($(this).attr('layer-idx'));
        $(this).attr('layer-idx', '');
    });
})();
</script>

<div class="{{$viewClass['form-group']}}">

    <label class="{{$viewClass['label']}} control-label">{!! $label !!}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <div class="input-group" style="max-width: 330px">
            <span class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="feather icon-calendar"></i></span>
            </span>
            <input autocomplete="off" type="text" class="form-control {{$rangeClass}}"
                   value="{{ (($value['start'] ?? '') !== '' && ($value['end'] ?? '') !== '') ? ($value['start'].' - '.$value['end']) : '' }}"
                   placeholder="点击选择起止时间" />
        </div>
        <input type="hidden" name="{{$startName}}" id="{{$rangeClass}}-start" value="{{ $value['start'] ?? '' }}" />
        <input type="hidden" name="{{$endName}}" id="{{$rangeClass}}-end" value="{{ $value['end'] ?? '' }}" />

        @include('admin::form.help-block')

    </div>
</div>

<script init=".{{$rangeClass}}">
    var $start = $('#{{$rangeClass}}-start'), $end = $('#{{$rangeClass}}-end');
    var fmt = '{{ $pickerFormat ?? 'YYYY-MM-DD HH:mm:ss' }}';

    // 中文月份：@moment 带 locales，取 zh-cn 数据但不切换全局 locale（不影响页面上其他 moment 组件）
    var zhMonths = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'];
    try {
        var zhData = moment.localeData('zh-cn');
        if (zhData && zhData.months() && zhData.months()[0] !== 'January') {
            zhMonths = zhData.months();
        }
    } catch (e) { /* 兜底用上面的硬编码 */ }

    var opts = {
        timePicker: {{ ($withTime ?? true) ? 'true' : 'false' }},
        timePicker24Hour: true,
        timePickerSeconds: {{ ($withTime ?? true) ? 'true' : 'false' }},
        showDropdowns: true,
        autoUpdateInput: false,
        locale: {
            format: fmt,
            separator: ' - ',
            applyLabel: '确定',
            cancelLabel: '清除',
            fromLabel: '开始',
            toLabel: '结束',
            customRangeLabel: '自定义',
            daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
            monthNames: zhMonths
        }
    };
    if ($start.val()) {
        opts.startDate = {{ ($withTime ?? true) ? "moment(\$start.val(), fmt)" : "moment(\$start.val().substring(0, 10), fmt)" }};
    }
    if ($end.val()) {
        opts.endDate = {{ ($withTime ?? true) ? "moment(\$end.val(), fmt)" : "moment(\$end.val().substring(0, 10), fmt)" }};
    }

    $this.daterangepicker(opts);

    // 确定才写回隐藏域；取消/清除时保留原值（不清空，避免误触丢数据）
    $this.on('apply.daterangepicker', function (ev, picker) {
        $start.val(picker.startDate.format(fmt));
        $end.val(picker.endDate.format(fmt));
        $(this).val(picker.startDate.format(fmt) + ' - ' + picker.endDate.format(fmt));
    });
</script>

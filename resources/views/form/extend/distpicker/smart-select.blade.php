<style>
    .smart-select-box .form-control{
        width: {{$input_width}} !important;
    }
</style>
<div class="smart-select-box {{$viewClass['form-group']}} {!! !$errors->hasAny($errorKey) ? '' : 'has-error' !!}">
    <label for="{{$id}}" class="{{$viewClass['label']}} control-label">{{$label}}</label>
    <div class="{{$viewClass['field']}} form-inline">
        @foreach($errorKey as $key => $col)
            @if($errors->has($col))
                @foreach($errors->get($col) as $message)
                    <label class="control-label" for="inputError">
                        <i class="fa fa-times-circle-o"></i> {{$message}}
                    </label>
                    <br/>
                @endforeach
            @endif
        @endforeach
        @php  $i= 0;
        $newname = array_keys($name);
        @endphp
        <div id="{{ $id }}" {!! $attributes !!}>
            @foreach($select_list as $key => $viewClass)
                <select class="form-control smart-sel"  id="sel-{{$key}}" next-level="{{!empty($newname[($i+1)]) ? $newname[($i+1)]:''}}" name="{{$key}}">
                    <option value="">{{$viewClass['label']}}</option>
                    @if($i == 0)
                        @foreach($options as $select => $option)
                            <option value="{{$select}}" {{ Dcat\Admin\Support\Helper::equal($select, $value) ?'selected':'' }}>{{$option}}</option>
                        @endforeach
                    @endif
                </select>&nbsp;
                @php  $i++ @endphp
            @endforeach
        </div>
        @include('admin::form.help-block')
    </div>
</div>
<script>
    Dcat.ready(function () {
        var namelist = '{!! json_encode($newname) !!}';
        var datajson = '{!! json_encode($select_list,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)!!}';
        $('.smart-sel').on('change',function () {
            var next_level = $(this).attr('next-level');
            if(next_level != ''){
                var new_next_level = 'sel-'+next_level;
                $('#'+new_next_level+' option:first').text('—— 加载中... ——');
                var selectedValue = $(this).val();
                if (datajson && typeof datajson === 'object' && !Array.isArray(datajson)) {
                } else {
                    datajson = JSON.parse(datajson);
                }
                if (Array.isArray(namelist)) {

                } else {
                    namelist = JSON.parse(namelist);
                }
                // 找到 '当前的' 的索引
                var index = namelist.indexOf(next_level);
                var result = namelist.slice(index + 1);
                // 循环剩余的对象
                //if(result.length > 0){
                    $.each(result, function(key, value) {
                        var Objectw = datajson[value];
                        console.log('value-value'+value);
                        // 在这里设置 HTML
                        $('#sel-'+value).html('<option value=""> '+Objectw.label+'</option>');
                    });
                //}
                var Objects = datajson[next_level];
                if (selectedValue) {
                    $.post({
                        url: '{{$selectValueUrl}}?model='+Objects.model+'&resp=['+Objects.resp+']&where='+Objects.where+'-' + selectedValue,
                        data: {},
                        success: function (res) {
                            $('#'+new_next_level).html(res);
                        }
                    });
                }

            }
        })
    });

</script>
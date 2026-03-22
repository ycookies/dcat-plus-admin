<div class="{{$viewClass['form-group']}}">

    <div  class="{{ $viewClass['label'] }} control-label">
        <span>{!! $label !!}</span>
    </div>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')
        <div class="input-group">
            <input type="hidden" name="{{$name}}"/>
            @if ($prepend)
                <span class="input-group-prepend"><span class="input-group-text bg-white">{!! $prepend !!}</span></span>
            @endif
            <select class="form-control {{$class}}" style="width: 100%;" name="{{$name}}" {!! $attributes !!} >
                <option value=""></option>
                @if($groups)
                    @foreach($groups as $group)
                        <optgroup label="{{ $group['label'] }}">
                            @foreach($group['options'] as $select => $option)
                                <option value="{{$select}}" {{ $select == $value ?'selected':'' }}>{{$option}}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                 @else
                    @foreach($options as $select => $option)
                        <option value="{{$select}}" {{ Dcat\Admin\Support\Helper::equal($select, $value) ?'selected':'' }}>{{$option}}</option>
                    @endforeach
                @endif
            </select>

            @if ($append)
                <span class="input-group-append"><span class="input-group-text bg-white" style="padding:.5rem .9rem;">{!! $append !!}</span></span>
            @endif
        </div>
        @include('admin::form.help-block')

    </div>
</div>

@include('admin::form.select-script')

<div class="{{$viewClass['form-group']}} {!! !$errors->hasAny($errorKey) ? '' : 'has-error' !!}">
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
        <div id="{{ $id }}" {!! $attributes !!}>
            @foreach($name as $viewClass)
                <select class="form-control" name="{{$viewClass}}"></select>&nbsp;
            @endforeach
        </div>
        @include('admin::form.help-block')
    </div>
</div>

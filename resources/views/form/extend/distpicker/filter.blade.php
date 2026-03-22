<div class="filter-input">
    <div class="input-group input-group-sm" id="{{ $id }}" data-value-type="code">
        <div class="input-group-prepend">
            <span class="input-group-text bg-white text-capitalize"  data-toggle="tooltip" data-placement="top"><b>{!! $label !!}</b></span>
        </div>
        @foreach($name as $viewClass)
            <select class="form-control distpicker_select" name="{{$viewClass}}"></select>&nbsp;
        @endforeach
    </div>
</div>

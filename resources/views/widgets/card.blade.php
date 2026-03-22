<style>
    .card-tools .btn{box-shadow:none !important;}
</style>
<div {!! $attributes !!}>
    @if ($title || $tools)
        <div class="card-header {{!empty($outline) ? 'card-outline '.$outline :''}} {{ $divider ? 'with-border' : '' }}">
            <span class="card-box-title">{!! $title !!}</span>
            @if($collapse)
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-minus"></i>
                </button>
            </div>
            @endif
            @if($remove)
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fa fa-times"></i>
                </button>
            </div>
            @endif
            @if(!empty($tools))
                <div class="box-tools pull-right">
                    @foreach($tools as $tool)
                        {!! $tool !!}
                    @endforeach
                </div>
            @endif
        </div>
    @endif
    <div class="card-body" style="{!! $padding !!}">
        {!! $content !!}
    </div>
    @if($footer)
    <div class="card-footer">
        {!! $footer !!}
    </div>
    @endif
</div>
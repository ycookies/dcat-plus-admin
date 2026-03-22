<div id="{{$id}}" class="btn-group-box" {!! $attributes !!}>
    <div class="{{$group_class}}">
        @foreach($items as $key => $item)
        <a href="{{$item['link']}}" type="button" class="btn {{$item['styles']}}">@if(!empty($item['icon']))<i class="{{$item['icon']}}"></i> @endif {{$item['btntxt']}}</a>
        @endforeach
    </div>
</div>

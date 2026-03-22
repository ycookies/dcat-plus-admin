
<div class="{{$id}}" id="{{$id}}"  style="margin-bottom: 20px">
    <ul class="list-group">
        @foreach($items as $key => $item)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="{{ $item['link'] }}" target="_blank">{{ $item['title'] }}</a>
            <span class="badge badge-secondary badge-pill">{{ $item['content'] }}</span>
        </li>
        @endforeach
    </ul>
</div>

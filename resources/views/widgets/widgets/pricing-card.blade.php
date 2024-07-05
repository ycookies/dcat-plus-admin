<style>

</style>
<div class="pricing-card {{$id}}">

    <div class="row">
        @foreach($items as $key => $item)
        <div class="col-sm-6 col-lg-{{$columns}}">
            <div class="card card-md shadow">
                @if(isset($item['active']))
                <div class="ribbon ribbon-top ribbon-bookmark bg-green">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"></path></svg>
                </div>
                @endif
                <div class="card-body @if($center) text-center @endif">
                    <div class="text-uppercase text-muted text-center font-weight-medium f22">{{$item['title']}}</div>
                    <div class="display-5 my-3 text-center">￥{{$item['money']}}</div>
                    <ul class="list-unstyled lh-lg">
                        <li><strong>{!! $item['head'] ?? '' !!}</strong></li>
                        @foreach($item['li'] as $keys =>  $items)
                            <li>
                                <i class="feather icon-check @if($items) text-success  @else text-danger @endif f22"></i> {{$keys}}
                            </li>
                        @endforeach
                        <li><strong>{!! $item['footer'] ?? '' !!}</strong></li>
                    </ul>
                    <div class="text-center mt-4">
                        <a href="javascript:void(0);" class="btn @if(isset($item['active'])) btn-success @endif w-100 dingyuePay" data-ver="">{{$item['btn'] ?? '订 购'}}</a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

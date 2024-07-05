<style>
    .carousel-box .carousel-control-prev,.carousel-box .carousel-control-next{
        border: 0px;
    }
    .carousel-control-next, .carousel-control-prev{
        display: flex;
        -ms-flex-align: center;
        align-items: center;
        -ms-flex-pack: center;
        justify-content: center;
        width: 15%;
        padding: 0;
        color: #fff;
        text-align: center;
        background: 0 0;
        border: 0;
        opacity: .5;
        transition: opacity .15s ease;
    }
    .carousel-box .d-md-block p{
        color: #dddddd;
    }
</style>
<div class="carousel-box">
    <div class="carousel slide" data-ride="carousel" style="width: 100%">
        <ol class="carousel-indicators">
            @foreach($items as $key => $item)
            <li data-target="#carouselExampleIndicators" data-slide-to="{{$key}}" class="{{$key == 0 ? 'active':''}}"></li>
            @endforeach
        </ol>
        <div class="carousel-inner">
            @foreach($items as $key => $item)
            <div class="carousel-item {{$key == 0 ? 'active':''}}">
                <a href="{{$item['link']}}">
                <img src="{{$item['img_src']}}" class="d-block w-100" alt="...">
                <div class="carousel-caption d-none d-md-block">
                    <h5>{{$item['title']}}</h5>
                    <p>{{$item['content']}}</p>
                </div>
                </a>
            </div>
            @endforeach
        </div>
        <button class="carousel-control-prev" type="button" data-target="#carouselExampleIndicators" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-target="#carouselExampleIndicators" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </button>
    </div>
</div>



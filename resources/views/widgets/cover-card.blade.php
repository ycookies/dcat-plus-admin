<style>
    .cover-card {

    }
    .cover-card .card-cover {
        position: relative;
        padding: 1rem 1rem;
        background: #666 no-repeat center/cover
    }

    .cover-card .card-cover:before {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        content: "";
        background: rgba(35,46,60,.48)
    }
    .cover-card .card-cover:first-child,.cover-card .card-cover:first-child:before {
        border-radius: 4px 4px 0 0
    }

    .cover-card .card-cover-blurred:before {
        -webkit-backdrop-filter: blur(2px);
        backdrop-filter: blur(2px)
    }
    .cover-card .avatar {
        --tblr-avatar-size: 2.5rem;
        position: relative;
        width: var(--tblr-avatar-size);
        height: var(--tblr-avatar-size);
        font-size: calc(var(--tblr-avatar-size)/ 2.8571429);
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #656d77;
        text-align: center;
        text-transform: uppercase;
        vertical-align: bottom;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background: #f0f2f6 no-repeat center/cover;
        border-radius: 4px
    }

    .cover-card .avatar svg {
        width: calc(var(--tblr-avatar-size)/ 1.6666667);
        height: calc(var(--tblr-avatar-size)/ 1.6666667)
    }

    .cover-card .avatar .badge {
        position: absolute;
        right: 0;
        bottom: 0;
        border-radius: 100rem;
        box-shadow: 0 0 0 2px #fff
    }
    .cover-card .avatar-rounded {
        border-radius: 100rem
    }
    .cover-card .avatar-xl {
        --tblr-avatar-size: 7rem !important;
    }
    .cover-card .card-body{
        flex: 1 1 auto;
        padding: 1rem 1rem;
    }
    .cover-card .card-title{
        float:none;
        display: block !important;
    }
</style>

<div class="cover-card">
    @foreach($items as $key => $item)
    <a class="card card-link" href="{{$item['link']}}">
        <div class="card-cover card-cover-blurred text-center"
             style="background-image: url({{$item['bg'] ?? ''}})">
            @if(!empty($item['avatar']))
            <span class="avatar avatar-xl avatar-thumb preview-img {{$item['avatar_circle']}} "
                  data-action='preview-img' src="{{$item['avatar']}}" style="background-image: url({{$item['avatar']}})"></span>
            @endif
        </div>
        <div class="card-body text-center">
            <div class="card-title">{{$item['title']}}</div>
            <div class="text-muted">{{$item['content']}}</div>
        </div>
    </a>
    @endforeach
</div>
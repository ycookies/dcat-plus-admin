<style>
    #{{ $id }}.dcat-carousel { position: relative; width: 100%; overflow: hidden; }
    #{{ $id }} .dcat-carousel-viewport { position: relative; width: 100%; overflow: hidden; transition: height .45s ease; }
    #{{ $id }} .dcat-carousel-track { display: flex; width: 100%; transition: transform .45s ease; will-change: transform; }
    #{{ $id }}[data-direction="vertical"] .dcat-carousel-track { flex-direction: column; }
    #{{ $id }} .dcat-carousel-slide { position: relative; flex: 0 0 100%; width: 100%; }
    #{{ $id }} .dcat-carousel-image { display: block; width: 100%; max-width: 100%; height: auto; object-fit: cover; }
    #{{ $id }} .dcat-carousel-caption { position: absolute; right: 12%; bottom: 1rem; left: 12%; color: #fff; text-align: center; text-shadow: 0 1px 3px rgba(0, 0, 0, .65); }
    #{{ $id }} .dcat-carousel-caption p { color: #ddd; margin-bottom: 0; }
    #{{ $id }} .dcat-carousel-control { position: absolute; top: 0; bottom: 0; z-index: 2; display: flex; align-items: center; justify-content: center; width: 15%; padding: 0; color: #fff; background: transparent; border: 0; cursor: pointer; opacity: .6; transition: opacity .15s ease; }
    #{{ $id }} .dcat-carousel-control:hover, #{{ $id }} .dcat-carousel-control:focus { color: #fff; opacity: 1; outline: 0; }
    #{{ $id }} .dcat-carousel-control--prev { left: 0; }
    #{{ $id }} .dcat-carousel-control--next { right: 0; }
    #{{ $id }} .dcat-carousel-control-icon { width: 1rem; height: 1rem; border-style: solid; border-width: 0 3px 3px 0; }
    #{{ $id }} .dcat-carousel-control--prev .dcat-carousel-control-icon { transform: rotate(135deg); }
    #{{ $id }} .dcat-carousel-control--next .dcat-carousel-control-icon { transform: rotate(-45deg); }
    #{{ $id }} .dcat-carousel-indicators { position: absolute; right: 0; bottom: .25rem; left: 0; z-index: 3; display: flex; justify-content: center; margin: 0; padding: 0; list-style: none; }
    #{{ $id }} .dcat-carousel-indicator { width: 8px; height: 8px; margin: 0 4px; padding: 0; border: 0; border-radius: 50%; background: rgba(255, 255, 255, .55); cursor: pointer; }
    #{{ $id }} .dcat-carousel-indicator.is-active { background: #fff; }
</style>

<div {!! $attributes !!} data-direction="{{ $direction }}" data-interval="{{ $interval }}" data-autoplay="{{ $autoplay ? '1' : '0' }}">
    <div class="dcat-carousel-viewport">
        <div class="dcat-carousel-track">
            @foreach($items as $key => $item)
                <div class="dcat-carousel-slide" data-index="{{ $key }}">
                    <a href="{{ !empty($item['link']) ? $item['link'] : 'javascript:void(0);' }}" @if(!empty($item['link'])) target="_blank" @endif>
                        <img src="{{ $item['img_src'] }}" class="dcat-carousel-image" alt="{{ $item['title'] }}">
                        @if($item['title'] || $item['content'])
                            <div class="dcat-carousel-caption">
                                @if($item['title'])<h5>{{ $item['title'] }}</h5>@endif
                                @if($item['content'])<p>{{ $item['content'] }}</p>@endif
                            </div>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    @if(count($items) > 1)
        @if($showArrows)
            <button class="dcat-carousel-control dcat-carousel-control--prev" type="button" aria-label="Previous slide">
                <span class="dcat-carousel-control-icon" aria-hidden="true"></span>
            </button>
            <button class="dcat-carousel-control dcat-carousel-control--next" type="button" aria-label="Next slide">
                <span class="dcat-carousel-control-icon" aria-hidden="true"></span>
            </button>
        @endif
        <ol class="dcat-carousel-indicators">
            @foreach($items as $key => $item)
                <li><button class="dcat-carousel-indicator {{ $key === 0 ? 'is-active' : '' }}" type="button" data-index="{{ $key }}" aria-label="Slide {{ $key + 1 }}"></button></li>
            @endforeach
        </ol>
    @endif
</div>

<script init="#{{ $id }}">
    var root = $this.get(0);

    if (!root || root.dataset.carouselInitialized) {
        return;
    }

    root.dataset.carouselInitialized = '1';

    var viewport = root.querySelector('.dcat-carousel-viewport');
    var track = root.querySelector('.dcat-carousel-track');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.dcat-carousel-slide'));
    var indicators = Array.prototype.slice.call(root.querySelectorAll('.dcat-carousel-indicator'));
    var direction = root.dataset.direction;
    var interval = parseInt(root.dataset.interval, 10) || 0;
    var autoplay = root.dataset.autoplay === '1';
    var activeIndex = 0;
    var timer;

    if (!slides.length) {
        return;
    }

    function updateViewportHeight() {
        if (direction === 'vertical') {
            viewport.style.height = slides[activeIndex].offsetHeight + 'px';
        }
    }

    function render() {
        if (direction === 'vertical') {
            updateViewportHeight();
            track.style.transform = 'translate3d(0, -' + slides[activeIndex].offsetTop + 'px, 0)';
        } else {
            track.style.transform = 'translate3d(-' + (activeIndex * 100) + '%, 0, 0)';
        }

        indicators.forEach(function (indicator, index) {
            indicator.classList.toggle('is-active', index === activeIndex);
        });
    }

    function goTo(index) {
        activeIndex = (index + slides.length) % slides.length;
        render();
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function start() {
        stop();

        if (autoplay && interval > 0 && slides.length > 1) {
            timer = window.setInterval(function () {
                goTo(activeIndex + 1);
            }, interval * 1000);
        }
    }

    var previous = root.querySelector('.dcat-carousel-control--prev');
    var next = root.querySelector('.dcat-carousel-control--next');

    previous && previous.addEventListener('click', function () {
        goTo(activeIndex - 1);
        start();
    });

    next && next.addEventListener('click', function () {
        goTo(activeIndex + 1);
        start();
    });

    indicators.forEach(function (indicator) {
        indicator.addEventListener('click', function () {
            goTo(parseInt(indicator.dataset.index, 10));
            start();
        });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    window.addEventListener('resize', render);

    root.querySelectorAll('.dcat-carousel-image').forEach(function (image) {
        image.addEventListener('load', render);
    });

    render();
    start();
</script>

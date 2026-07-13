<style>
    #{{ $id }}.dcat-honor-wall {
        --dcat-honor-card-width: {{ $slideWidth }}px;
        --dcat-honor-card-height: 252px;
        --dcat-honor-nav-top: 150px;
        --dcat-honor-title-height: 48px;
        --dcat-honor-title-font: 14px;
        box-sizing: border-box;
        width: min(100%, {{ $maxWidth }}px);
        margin: 0 auto;
        padding: 24px 46px 14px;
        overflow: hidden;
    }
    #{{ $id }}.dcat-honor-wall .swiper-wrapper { height: var(--dcat-honor-card-height); align-items: center; }
    #{{ $id }}.dcat-honor-wall .swiper-slide {
        width: 100%;
        height: var(--dcat-honor-card-height);
        filter: drop-shadow(0 4px 7px rgba(38, 48, 76, .1));
        transition-property: transform, opacity, filter !important;
        will-change: transform, filter;
    }
    #{{ $id }}.dcat-honor-wall .swiper-slide.is-near { filter: drop-shadow(0 8px 13px rgba(38, 48, 76, .17)); }
    #{{ $id }}.dcat-honor-wall .swiper-slide-active { filter: drop-shadow(0 16px 22px rgba(38, 48, 76, .27)); }
    #{{ $id }} .dcat-honor-wall__link { display: block; color: inherit; text-decoration: none; }
    #{{ $id }} .dcat-honor-wall__card {
        box-sizing: border-box;
        width: var(--dcat-honor-card-width);
        margin: 0 auto;
        overflow: hidden;
        border: 1px solid #e7e9ee;
        border-radius: 7px;
        background: #fff;
        transform: translateX(0);
        transition: transform .65s ease, box-shadow .3s ease;
        will-change: transform;
    }
    #{{ $id }} .dcat-honor-wall__image {
        position: relative;
        width: 100%;
        aspect-ratio: 260 / 204;
        overflow: hidden;
        background: #f5f6f8;
    }
    #{{ $id }} .dcat-honor-wall__image img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
    }
    #{{ $id }} .dcat-honor-wall__title {
        display: -webkit-box;
        box-sizing: border-box;
        height: var(--dcat-honor-title-height);
        padding: 9px 14px;
        overflow: hidden;
        color: #5b6472;
        font-size: var(--dcat-honor-title-font);
        line-height: 1.45;
        text-align: center;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }
    #{{ $id }} .dcat-honor-wall__controls {
        position: relative;
        z-index: 1200;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        margin-top: 14px;
    }
    #{{ $id }} .dcat-honor-wall__pagination { display: flex; align-items: center; justify-content: center; gap: 7px; }
    #{{ $id }} .dcat-honor-wall__bullet {
        width: 8px;
        height: 8px;
        padding: 0;
        border: 0;
        border-radius: 999px;
        background: #d5d9e3;
        box-shadow: none;
        cursor: pointer;
        opacity: .9;
        transition: width .25s ease, background-color .25s ease, opacity .25s ease, transform .25s ease;
    }
    #{{ $id }} .dcat-honor-wall__bullet:hover { background: #aeb7ca; opacity: 1; transform: scale(1.12); }
    #{{ $id }} .dcat-honor-wall__bullet.is-active { width: 24px; background: var(--primary, #586cb1); opacity: 1; }
    #{{ $id }} .swiper-button-prev, #{{ $id }} .swiper-button-next {
        top: var(--dcat-honor-nav-top);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        margin-top: -19px;
        padding: 0;
        border: 1px solid rgba(88, 108, 177, .22);
        border-radius: 12px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 6px 18px rgba(38, 48, 76, .14);
        color: var(--primary, #586cb1);
        cursor: pointer;
        backdrop-filter: blur(8px);
        transition: color .2s ease, border-color .2s ease, background-color .2s ease, box-shadow .2s ease, transform .2s ease;
        z-index: 1100;
    }
    #{{ $id }} .swiper-button-prev::after, #{{ $id }} .swiper-button-next::after,
    #{{ $id }} .swiper-button-prev .swiper-navigation-icon, #{{ $id }} .swiper-button-next .swiper-navigation-icon { display: none; }
    #{{ $id }} .swiper-button-prev .feather, #{{ $id }} .swiper-button-next .feather { font-size: 20px; line-height: 1; }
    #{{ $id }} .swiper-button-prev:hover, #{{ $id }} .swiper-button-next:hover {
        border-color: var(--primary, #586cb1);
        background: var(--primary, #586cb1);
        box-shadow: 0 8px 20px rgba(88, 108, 177, .28);
        color: #fff;
        transform: translateY(-1px);
    }
    #{{ $id }} .swiper-button-prev:active, #{{ $id }} .swiper-button-next:active { transform: translateY(0) scale(.96); }
    #{{ $id }} .swiper-button-prev { left: 8px; }
    #{{ $id }} .swiper-button-next { right: 8px; }
    body.dark-mode #{{ $id }} .dcat-honor-wall__card { border-color: #484864; background: #242436; box-shadow: 0 8px 24px rgba(0, 0, 0, .32); }
    body.dark-mode #{{ $id }}.dcat-honor-wall .swiper-slide { filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .28)); }
    body.dark-mode #{{ $id }}.dcat-honor-wall .swiper-slide.is-near { filter: drop-shadow(0 8px 14px rgba(0, 0, 0, .38)); }
    body.dark-mode #{{ $id }}.dcat-honor-wall .swiper-slide-active { filter: drop-shadow(0 17px 24px rgba(0, 0, 0, .5)); }
    body.dark-mode #{{ $id }} .dcat-honor-wall__image { background: #1d1d2e; }
    body.dark-mode #{{ $id }} .dcat-honor-wall__title { color: #d7d7e4; }
    body.dark-mode #{{ $id }} .dcat-honor-wall__bullet { background: #64647d; }
    body.dark-mode #{{ $id }} .dcat-honor-wall__bullet.is-active { background: var(--primary, #7c8fd4); }
    body.dark-mode #{{ $id }} .swiper-button-prev, body.dark-mode #{{ $id }} .swiper-button-next { border-color: #50506b; background: rgba(44, 44, 66, .92); }
    body.dark-mode #{{ $id }} .swiper-button-prev:hover, body.dark-mode #{{ $id }} .swiper-button-next:hover { border-color: var(--primary, #7c8fd4); background: var(--primary, #7c8fd4); color: #fff; }
    @media (max-width: 575.98px) {
        #{{ $id }}.dcat-honor-wall { padding-right: 36px; padding-left: 36px; }
    }
</style>

<div id="{{ $id }}" {!! $attributes !!}>
    <div class="swiper-wrapper">
        @for($copy = 0; $copy < 3; $copy++)
            @foreach($slides as $index => $slide)
                <div class="swiper-slide" data-honor-index="{{ $index }}">{!! $slide !!}</div>
            @endforeach
        @endfor
    </div>
    @if($pagination)
        <div class="dcat-honor-wall__controls">
            <div class="dcat-honor-wall__pagination" aria-label="荣誉墙分页">
                @foreach($slides as $index => $slide)
                    <button type="button" class="dcat-honor-wall__bullet" data-honor-bullet="{{ $index }}" aria-label="查看第 {{ $index + 1 }} 项"></button>
                @endforeach
            </div>
        </div>
    @endif
    @if($navigation)
        <button type="button" class="swiper-button-prev" aria-label="上一项"><i class="feather icon-chevron-left"></i></button>
        <button type="button" class="swiper-button-next" aria-label="下一项"><i class="feather icon-chevron-right"></i></button>
    @endif
</div>

<script init="#{{ $id }}">
    var element = $this.get(0);

    if (!element || element.dataset.swiperInitialized || !window.Swiper) {
        return;
    }

    var slideCount = {{ count($slides) }};

    if (!slideCount) {
        return;
    }

    element.dataset.swiperInitialized = '1';

    var autoScale = {{ $autoScale ? 'true' : 'false' }};
    var fixedSlideWidth = {{ $slideWidth }};
    var configuredVisibleSlides = {{ $visibleSlides }};
    var lastContainerWidth = 0;
    var options = {!! admin_javascript_json($options) !!};
    var previousEvents = options.on || {};
    var previousInit = previousEvents.init;
    var previousActiveIndexChange = previousEvents.activeIndexChange;
    var previousTransitionEnd = previousEvents.transitionEnd;
    var centerGapOffset = 0;

    var setCreativeTranslate = function (containerWidth, cardWidth, swiper) {
        var horizontalPadding = containerWidth < 576 ? 36 : 46;
        var outerCardWidth = cardWidth * .44;
        var availableHalfWidth = containerWidth / 2 - horizontalPadding - outerCardWidth / 2 - 10;
        var swiperContentWidth = Math.max(1, containerWidth - horizontalPadding * 2);
        var stepWidth = Math.max(0, availableHalfWidth) * 3 / 7;
        var translatePercent = Math.max(12, Math.min(20, stepWidth / swiperContentWidth * 100));
        var actualStepWidth = swiperContentWidth * translatePercent / 100;
        var prevTranslate = -translatePercent.toFixed(2) + '%';
        var nextTranslate = translatePercent.toFixed(2) + '%';

        centerGapOffset = actualStepWidth / 3;

        options.creativeEffect = options.creativeEffect || {};
        options.creativeEffect.prev = options.creativeEffect.prev || {};
        options.creativeEffect.next = options.creativeEffect.next || {};
        if (!Array.isArray(options.creativeEffect.prev.translate)) {
            options.creativeEffect.prev.translate = [prevTranslate, 0, -180];
        }
        if (!Array.isArray(options.creativeEffect.next.translate)) {
            options.creativeEffect.next.translate = [nextTranslate, 0, -180];
        }
        options.creativeEffect.prev.translate[0] = prevTranslate;
        options.creativeEffect.next.translate[0] = nextTranslate;

        if (swiper && swiper.params && swiper.params.creativeEffect && swiper.params.creativeEffect.prev && swiper.params.creativeEffect.next) {
            swiper.params.creativeEffect.prev.translate[0] = prevTranslate;
            swiper.params.creativeEffect.next.translate[0] = nextTranslate;
        }
    };

    var updateDimensions = function () {
        var containerWidth = element.clientWidth || (element.parentElement ? element.parentElement.clientWidth : fixedSlideWidth);
        var cardWidth;

        if (autoScale) {
            cardWidth = containerWidth < 576
                ? Math.max(180, Math.min(containerWidth - 72, containerWidth * .8))
                : Math.max(240, Math.min(520, containerWidth * .44));
        } else {
            cardWidth = Math.min(fixedSlideWidth, Math.max(160, containerWidth - 72));
        }

        var titleFont = Math.max(12, Math.min(20, cardWidth / 27));
        var titleHeight = Math.max(42, Math.min(72, cardWidth * .15));
        var cardHeight = cardWidth * (204 / 260) + titleHeight;
        var navigationTop = 24 + cardHeight / 2;

        setCreativeTranslate(containerWidth, cardWidth, element.swiper);

        element.style.setProperty('--dcat-honor-card-width', cardWidth + 'px');
        element.style.setProperty('--dcat-honor-card-height', cardHeight + 'px');
        element.style.setProperty('--dcat-honor-nav-top', navigationTop + 'px');
        element.style.setProperty('--dcat-honor-title-font', titleFont + 'px');
        element.style.setProperty('--dcat-honor-title-height', titleHeight + 'px');

        return containerWidth;
    };

    var realIndex = function (swiper) {
        return ((swiper.activeIndex % slideCount) + slideCount) % slideCount;
    };

    var updateStage = function (swiper) {
        var containerWidth = element.clientWidth || 0;
        var visibleSlides = containerWidth < 576 ? Math.min(3, configuredVisibleSlides) : configuredVisibleSlides;
        var radius = Math.min(Math.floor(visibleSlides / 2), Math.floor((slideCount - 1) / 2));
        var slides = swiper.slides || [];
        var currentRealIndex = realIndex(swiper);
        var bullets = element.querySelectorAll('[data-honor-bullet]');

        for (var index = 0; index < slides.length; index++) {
            var distance = Math.abs(index - swiper.activeIndex);
            var visible = distance <= radius;
            var card = slides[index].querySelector('.dcat-honor-wall__card');
            var direction = index < swiper.activeIndex ? -1 : 1;
            var effectiveScale = distance === 1 ? .68 : .44;
            var cardOffset = distance ? direction * centerGapOffset / effectiveScale : 0;

            slides[index].style.opacity = visible ? '1' : '0';
            slides[index].style.visibility = visible ? 'visible' : 'hidden';
            slides[index].style.pointerEvents = visible ? 'auto' : 'none';
            slides[index].style.left = '0px';
            if (card) {
                card.style.transform = 'translateX(' + cardOffset + 'px)';
                card.style.transitionDuration = (options.speed || 650) + 'ms, 300ms';
            }
            slides[index].classList.toggle('is-near', distance === 1);
            slides[index].classList.toggle('is-far', distance >= 2);
        }

        for (var bulletIndex = 0; bulletIndex < bullets.length; bulletIndex++) {
            bullets[bulletIndex].classList.toggle('is-active', bulletIndex === currentRealIndex);
        }
    };

    var resetToMiddleCopy = function (swiper) {
        if (swiper.activeIndex < slideCount || swiper.activeIndex >= slideCount * 2) {
            swiper.slideTo(slideCount + realIndex(swiper), 0, false);
        }

        updateStage(swiper);
    };

    options.loop = false;
    options.slidesPerView = 1;
    options.centeredSlides = true;
    options.initialSlide = slideCount + Math.floor(slideCount / 2);
    options.pagination = false;
    if (options.navigation && typeof options.navigation === 'object') {
        options.navigation.addIcons = false;
    }
    options.on = previousEvents;
    options.on.init = function (swiper) {
        updateStage(swiper || this);

        if (typeof previousInit === 'function') {
            previousInit.apply(this, arguments);
        }
    };
    options.on.activeIndexChange = function (swiper) {
        updateStage(swiper || this);

        if (typeof previousActiveIndexChange === 'function') {
            previousActiveIndexChange.apply(this, arguments);
        }
    };
    options.on.transitionEnd = function (swiper) {
        resetToMiddleCopy(swiper || this);

        if (typeof previousTransitionEnd === 'function') {
            previousTransitionEnd.apply(this, arguments);
        }
    };

    updateDimensions();
    element.swiper = new window.Swiper(element, options);

    var bullets = element.querySelectorAll('[data-honor-bullet]');

    for (var bulletIndex = 0; bulletIndex < bullets.length; bulletIndex++) {
        bullets[bulletIndex].addEventListener('click', function () {
            element.swiper.slideTo(slideCount + Number(this.getAttribute('data-honor-bullet')), options.speed);
        });
    }

    if (slideCount <= 1) {
        var navigationButtons = element.querySelectorAll('.swiper-button-prev, .swiper-button-next');

        for (var buttonIndex = 0; buttonIndex < navigationButtons.length; buttonIndex++) {
            navigationButtons[buttonIndex].style.display = 'none';
        }
    }

    if (window.ResizeObserver) {
        var resizeFrame = null;
        var resizeObserver = new ResizeObserver(function () {
            var containerWidth = element.clientWidth || 0;

            if (Math.abs(containerWidth - lastContainerWidth) < 1) {
                return;
            }

            lastContainerWidth = containerWidth;

            if (resizeFrame) {
                cancelAnimationFrame(resizeFrame);
            }

            resizeFrame = requestAnimationFrame(function () {
                updateDimensions();
                element.swiper.update();
                if (typeof element.swiper.setTranslate === 'function') {
                    element.swiper.setTranslate(element.swiper.translate);
                }
                if (typeof element.swiper.updateAutoHeight === 'function') {
                    element.swiper.updateAutoHeight(0);
                }
                updateStage(element.swiper);
            });
        });

        resizeObserver.observe(element);
        element.dcatHonorWallResizeObserver = resizeObserver;
    }
</script>

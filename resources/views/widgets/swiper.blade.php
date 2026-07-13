<style>
    #{{ $id }}.dcat-swiper { position: relative; width: 100%; overflow: hidden; }
    #{{ $id }} .swiper-slide { box-sizing: border-box; }
    #{{ $id }} .swiper-button-prev, #{{ $id }} .swiper-button-next { color: var(--primary, #586cb1); }
    #{{ $id }} .swiper-pagination-bullet-active { background: var(--primary, #586cb1); }
    #{{ $id }}.dcat-marquee-notice { height: 40px; border: 1px solid #e4e7ed; border-radius: 4px; background: #fff; }
    #{{ $id }}.dcat-marquee-notice .swiper-slide { display: flex; align-items: center; height: 40px; padding: 0 14px; }
    #{{ $id }} .dcat-marquee-notice__link { width: 100%; color: inherit; text-decoration: none; }
    #{{ $id }} .dcat-marquee-notice__item { display: flex; align-items: center; gap: 8px; width: 100%; overflow: hidden; color: #606266; }
    #{{ $id }} .dcat-marquee-notice__item .feather { flex: 0 0 auto; color: #e6a23c; }
    #{{ $id }} .dcat-marquee-notice__item span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    body.dark-mode #{{ $id }}.dcat-marquee-notice { border-color: #484864; background: #242436; }
    body.dark-mode #{{ $id }} .dcat-marquee-notice__item { color: #d7d7e4; }
</style>

<div id="{{ $id }}" {!! $attributes !!}>
    <div class="swiper-wrapper">
        @foreach($slides as $slide)
            <div class="swiper-slide">{!! $slide !!}</div>
        @endforeach
    </div>
    @if($pagination)
        <div class="swiper-pagination"></div>
    @endif
    @if($navigation)
        <div class="swiper-button-prev" aria-label="上一页"></div>
        <div class="swiper-button-next" aria-label="下一页"></div>
    @endif
</div>

<script init="#{{ $id }}">
    var element = $this.get(0);

    if (!element || element.dataset.swiperInitialized || !window.Swiper) {
        return;
    }

    element.dataset.swiperInitialized = '1';
    element.swiper = new window.Swiper(element, {!! admin_javascript_json($options) !!});
</script>

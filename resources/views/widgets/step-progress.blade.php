<div class="step-progress step-progress--{{ $style }}" {!! $attributes !!}>
    @foreach($steps as $step)
        <div class="step-progress__item step-progress__item--{{ $step['state'] }}">
            @if($style == 'arrow')
                <span class="step-progress__num">{{ $step['num'] }}</span>
                @if($step['description'])
                    <span class="step-progress__title tips"   data-title="{{ $step['description'] }}">{{ $step['title'] }}</span>
                @else 
                    <span class="step-progress__title " >{{ $step['title'] }}</span>
                @endif
            @else
                <span class="step-progress__circle">
                    <span class="step-progress__num">{{ $step['num'] }}</span>
                    <i class="step-progress__check feather icon-check"></i>
                </span>
                @if($step['description'])
                    <span class="step-progress__title tips"   data-title="{{ $step['description'] }}">{{ $step['title'] }}</span>
                @else 
                    <span class="step-progress__title " >{{ $step['title'] }}</span>
                @endif
            @endif
        </div>
    @endforeach
</div>

<style>
.step-progress{display:flex;width:100%;padding:8px 4px;}
.step-progress__item{position:relative;flex:1;min-width:0;display:flex;flex-direction:column;align-items:center;gap:8px;}
/* 连接线：完成步之后的连线为主题色 */
.step-progress__item:not(:last-child)::after{content:'';position:absolute;top:20px;left:calc(50% + 26px);width:calc(100% - 52px);height:3px;border-radius:2px;background:#e2e6ee;}
.step-progress__item--done:not(:last-child)::after{background:var(--sp-primary);}
.step-progress__circle{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:#fff;border:2px solid #d3d9e3;font-size:16px;font-weight:600;color:#9aa0ac;}
.step-progress__item--done .step-progress__circle{background:var(--sp-primary);border-color:var(--sp-primary);color:#fff;}
.step-progress__item--current .step-progress__circle{border-color:var(--sp-primary);color:var(--sp-primary);box-shadow:0 0 0 4px var(--sp-primary-halo);}
.step-progress__title{font-size:14px;color:#6b7280;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:default;}
.step-progress__item--done .step-progress__title{color:#333;}
.step-progress__item--current .step-progress__title{color:var(--sp-primary);font-weight:600;}
.step-progress__item--todo .step-progress__title{color:#b0b7c3;}
/* 打勾风格：完成步圆点显示 ✓ */
.step-progress__check{display:none;font-size:18px;}
.step-progress--check .step-progress__item--done .step-progress__num{display:none;}
.step-progress--check .step-progress__item--done .step-progress__check{display:inline-block;}
/* 箭头分段式 */
.step-progress--arrow{padding:6px 0;}
.step-progress--arrow .step-progress__item{flex-direction:row;align-items:center;justify-content:flex-start;gap:10px;padding:13px 22px 13px 32px;background:#f3f5f9;color:#9aa0ac;clip-path:polygon(0 0, calc(100% - 16px) 0, 100% 50%, calc(100% - 16px) 100%, 0 100%, 16px 50%);transition:filter .18s ease;}
.step-progress--arrow .step-progress__item + .step-progress__item{margin-left:-18px;}
.step-progress--arrow .step-progress__item::after{display:none;}
.step-progress--arrow .step-progress__item--todo:hover{filter:brightness(.96);}
.step-progress--arrow .step-progress__num{display:inline-flex;align-items:center;justify-content:center;flex:none;width:22px;height:22px;border-radius:50%;font-size:12px;font-weight:700;background:rgba(31,42,68,.08);}
.step-progress--arrow .step-progress__item--done{background:linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-deep) 100%);color:#fff;filter:drop-shadow(0 4px 10px var(--sp-primary-halo));}
.step-progress--arrow .step-progress__item--done .step-progress__num{background:rgba(255,255,255,.22);}
.step-progress--arrow .step-progress__item--done .step-progress__title{color:#fff;font-weight:600;}
.step-progress--arrow .step-progress__item--current{background:var(--sp-primary-halo);color:var(--sp-primary);font-weight:600;}
.step-progress--arrow .step-progress__item--current .step-progress__num{background:var(--sp-primary);color:#fff;}
/* 深色模式 */
body.dark-mode .step-progress__item:not(:last-child)::after{background:#3e434c;}
body.dark-mode .step-progress__item--done:not(:last-child)::after{background:var(--sp-primary);}
body.dark-mode .step-progress__circle{background:#2a2e35;border-color:#4b515c;color:#7d8492;}
body.dark-mode .step-progress__item--done .step-progress__circle{background:var(--sp-primary);border-color:var(--sp-primary);color:#fff;}
body.dark-mode .step-progress__item--current .step-progress__circle{background:#2a2e35;border-color:var(--sp-primary);color:var(--sp-primary);}
body.dark-mode .step-progress__title{color:#8b93a1;}
body.dark-mode .step-progress__item--done .step-progress__title{color:#d5d9e0;}
body.dark-mode .step-progress__item--current .step-progress__title{color:#c3cbff;}
body.dark-mode .step-progress__item--todo .step-progress__title{color:#6b7280;}
body.dark-mode .step-progress--arrow .step-progress__item{background:#33373f;color:#7d8492;}
body.dark-mode .step-progress--arrow .step-progress__num{background:rgba(255,255,255,.08);}
body.dark-mode .step-progress--arrow .step-progress__item--done{background:linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-deep) 100%);color:#fff;}
body.dark-mode .step-progress--arrow .step-progress__item--done .step-progress__num{background:rgba(255,255,255,.22);}
body.dark-mode .step-progress--arrow .step-progress__item--done .step-progress__title{color:#fff;}
body.dark-mode .step-progress--arrow .step-progress__item--current{background:#2a2e35;color:#c3cbff;filter:drop-shadow(0 3px 8px rgba(0,0,0,.35));}
body.dark-mode .step-progress--arrow .step-progress__item--current .step-progress__num{background:var(--sp-primary);color:#fff;}
</style>

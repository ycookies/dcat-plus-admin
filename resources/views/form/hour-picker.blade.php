<style>
.hp-picker{position:relative}
.hp-trigger{cursor:pointer;background:#fff;color:#25567d;font-weight:600}
.hp-trigger:hover{border-color:#3c8dbc}
.hp-panel{display:none;position:absolute;top:100%;left:0;z-index:99;width:100%;background:#fff;
    border:1px solid #d2d6de;border-radius:4px;box-shadow:0 6px 18px rgba(0,0,0,.15);padding:8px;margin-top:4px}
.hp-group{display:flex;align-items:flex-start;margin-bottom:6px}
.hp-glabel{flex:0 0 34px;font-size:12px;color:#999;padding-top:4px}
.hp-btns{display:flex;flex-wrap:wrap;gap:4px;flex:1}
.hp-btn{border:1px solid #d2d6de;background:#f7f9fb;border-radius:3px;font-size:12px;
    padding:3px 8px;cursor:pointer;color:#333;line-height:1.4}
.hp-btn:hover{border-color:#3c8dbc;color:#3c8dbc}
.hp-btn.active{background:#3c8dbc;border-color:#3c8dbc;color:#fff;font-weight:600}
.hp-clear{width:100%;border:none;background:#f5f5f5;color:#999;font-size:12px;padding:4px;border-radius:3px;cursor:pointer}
.hp-clear:hover{background:#fbe9e9;color:#e74c3c}
</style>

<div class="{!! $viewClass['form-group'] !!}" {!! $attributes !!}>
    <div class="{!! $viewClass['label'] !!} control-label">
        <span>{!! $label !!}</span>
    </div>
    <div class="{!! $viewClass['field'] !!}">
        @include('admin::form.error')

        <div class="hp-picker">
            <input type="hidden" class="hp-val" name="{!! $name !!}" value="{!! $value !!}">
            <div class="hp-trigger form-control">🕐 <span class="hp-text">{{ $value ?: $placeholder }}</span> ▾</div>
            <div class="hp-panel">
                @foreach ([['凌晨', 0, 6], ['上午', 6, 12], ['下午', 12, 18], ['晚上', 18, 24]] as [$group, $from, $to])
                    @php($groupSlots = collect($slots)->filter(fn ($s) => (int) substr($s, 0, 2) >= $from && (int) substr($s, 0, 2) < $to)->values())
                    @if ($groupSlots->isNotEmpty())
                        <div class="hp-group">
                            <span class="hp-glabel">{{ $group }}</span>
                            <span class="hp-btns">
                                @foreach ($groupSlots as $slot)
                                    <button type="button" class="hp-btn {{ $value === $slot ? 'active' : '' }}" data-h="{{ $slot }}">{{ $slot }}</button>
                                @endforeach
                            </span>
                        </div>
                    @endif
                @endforeach
                <button type="button" class="hp-clear">✕ 清空</button>
            </div>
        </div>

        @include('admin::form.help-block')

    </div>
</div>

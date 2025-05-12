@if(isset($card))
<div class="card {{$shadow}}">
    @if(isset($header))
    <div class="box-header with-border">
        {!! $header !!}
    </div>
    @endif
    
    <div class="card-body">
        <div class="descriptions-{{ $columns ?? 1 }}-columns">
            @foreach($items as $item)
            <div class="descriptions-row {{ isset($item['dedicated_line']) ? 'dedicated-line' : '' }}">
                <div class="descriptions-label {{$tips}}" data-title="{{ $item['label'] }}">{{ $item['label'] }}</div>
                <div class="descriptions-content {{$tips}}" data-title="{{ is_string($item['content']) ? $item['content'] : '' }}">
                    {!! $item['content'] !!}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @if(isset($footer))
    <div class="card-footer">
        {!! $footer !!}
    </div>
    @endif
</div>
@else
<div class="descriptions-{{ $columns ?? 1 }}-columns">
    @foreach($items as $item)
    <div class="descriptions-row {{ isset($item['dedicated_line']) ? 'dedicated-line' : '' }}">
        <div class="descriptions-label {{$tips}}" data-title="{{ $item['label'] }}">{{ $item['label'] }}</div>
        <div class="descriptions-content {{$tips}}" data-title="{{ is_string($item['content']) ? $item['content'] : '' }}">
            {!! $item['content'] !!}
        </div>
    </div>
    @endforeach
</div>
@endif

<style>
.descriptions-1-columns,
.descriptions-2-columns,
.descriptions-3-columns {
    width: 100%;
    border: 1px solid #ececec;
    border-radius: 5px;
    overflow: hidden;
}

.descriptions-row {
    display: flex;
    border-bottom: 1px solid #f0f0f0;
    background: #fff;
}

.descriptions-row:last-child {
    border-bottom: none;
}

.descriptions-label {
    min-width: 100px;
    max-width: 100px;
    padding: 12px 24px;
    background-color: #fafafa;
    font-weight: bold;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-right: 1px solid #f0f0f0;
    display: flex;
    align-items: center; /* 垂直居中 */
    justify-content: center; /* 水平居中 */
}

.descriptions-content {
    flex: 1;
    padding: 12px 24px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 独占整行样式 */
.descriptions-row.dedicated-line {
    width: 100% !important;
    float: none !important;
    border-right: none !important;
}

.descriptions-row.dedicated-line .descriptions-content {
    white-space: normal;
}

@media screen and (min-width: 768px) {
    .descriptions-2-columns .descriptions-row:not(.dedicated-line) {
        width: 50%;
        float: left;
        border-right: 1px solid #f0f0f0;
    }

    .descriptions-3-columns .descriptions-row:not(.dedicated-line) {
        width: 33.33%;
        float: left;
        border-right: 1px solid #f0f0f0;
    }
}

@media screen and (max-width: 767px) {
    .descriptions-2-columns .descriptions-row,
    .descriptions-3-columns .descriptions-row {
        width: 100%;
        float: none;
        border-right: none;
    }
}

/* Clear float */
.descriptions-2-columns::after,
.descriptions-3-columns::after {
    content: '';
    display: table;
    clear: both;
}

/* 最后一行的右边框处理 */
@media screen and (min-width: 768px) {
    .descriptions-2-columns .descriptions-row:not(.dedicated-line):nth-child(2n),
    .descriptions-3-columns .descriptions-row:not(.dedicated-line):nth-child(3n) {
        border-right: none;
    }
}

/* Card 样式优化 */
.card {
    margin-bottom: 1rem;
}

.box-header.with-border {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.card-footer {
    background-color: #fff;
    border-top: 1px solid #f0f0f0;
    padding: 1rem;
}
</style> 
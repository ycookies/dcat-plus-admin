<div class="erp-dashboard">
    <div class="erp-dashboard__intro">
        <div><span class="erp-dashboard__kicker"><i class="feather icon-layout"></i> ERP WORKSPACE</span><h2>经营驾驶舱</h2><p>销售、库存、客户与履约数据一屏协同。</p></div>
        <div class="erp-dashboard__date"><i class="feather icon-calendar"></i>{{ $data['period'] }}</div>
    </div>

    <div class="erp-dashboard__hero">
        <div class="erp-dashboard__banner-column">{!! $primaryBannerSwiper !!}</div>
        <div class="erp-dashboard__banner-column">{!! $operationsBannerSwiper !!}</div>
    </div>

    <div class="erp-notice-bar">
        <div class="erp-notice-bar__label"><span><i class="feather icon-bell"></i></span><strong>运营公告</strong></div>
        <div class="erp-notice-bar__content">{!! $marqueeNotice !!}</div>
        <span class="erp-notice-bar__state"><i class="feather icon-radio"></i> 实时同步</span>
    </div>

    <div class="row">
        @foreach($data['overview'] as $metric)
            <div class="col-xl-3 col-md-6">
                <div class="card erp-overview-card erp-overview-card--{{ $metric['color'] }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="erp-overview-label">{{ $metric['label'] }}</span>
                            <span class="erp-overview-icon bg-light-{{ $metric['color'] }} text-{{ $metric['color'] }}"><i class="{{ $metric['icon'] }}"></i></span>
                        </div>
                        <div class="erp-overview-value">{{ $metric['value'] }}</div>
                        <div class="erp-overview-sub"><span class="{{ $metric['trend'] }}"><i class="feather icon-trending-{{ $metric['trend'] === 'down' ? 'down' : 'up' }}"></i></span> {{ $metric['subtext'] }}</div>
                        <svg class="erp-sparkline" viewBox="0 0 160 48" preserveAspectRatio="none" aria-hidden="true">
                            @php($points = collect($metric['sparkline'])->map(function ($value, $index) use ($metric) { $min = min($metric['sparkline']); $max = max($metric['sparkline']); $range = max(1, $max - $min); return round($index * (160 / (count($metric['sparkline']) - 1)), 2).','.round(43 - (($value - $min) / $range * 28), 2); })->implode(' '))
                            <polyline points="{{ $points }}" fill="none" stroke="{{ ['primary' => '#5b6df9', 'warning' => '#f6a23c', 'danger' => '#df5b69', 'success' => '#18b67a'][$metric['color']] }}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card erp-dashboard-card">
                <div class="card-header">
                    <div class="erp-panel-head">
                        <div><span class="card-box-title">销售趋势</span><div class="erp-muted mt-1">近 7 日实际销售额与目标达成情况</div></div>
                        <div class="text-right"><div class="erp-panel-value">{{ $data['salesTrend']['total'] }}</div><div class="erp-panel-hint"><i class="feather icon-trending-up"></i> {{ $data['salesTrend']['growth'] }} 同比上周</div></div>
                    </div>
                </div>
                <div class="card-body pt-2"><div class="erp-chart-wrap">{!! $salesChart !!}</div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card erp-dashboard-card">
                <div class="card-header"><span class="card-box-title">渠道结构</span><div class="erp-muted mt-1">销售额占比 · 多渠道统一管理</div></div>
                <div class="card-body pt-1"><div class="erp-channel-chart">{!! $channelChart !!}</div></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">{!! $recentOrders !!}</div>
        <div class="col-xl-4">
            <div class="card erp-dashboard-card">
                <div class="card-header"><span class="card-box-title">履约进度</span><div class="erp-muted mt-1">订单处理与发货目标</div></div>
                <div class="card-body erp-fulfillment">
                    {!! $fulfillmentChart !!}
                    <div class="erp-fulfillment-meta">
                        <div><span>{{ $data['fulfillment']['target'] }}</span><strong>{{ $data['fulfillment']['completed'] }}</strong></div>
                        <div class="text-right"><span>当前待处理</span><strong class="text-warning">{{ $data['fulfillment']['pending'] }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-5">
            <div class="card erp-dashboard-card">
                <div class="card-header"><span class="card-box-title">品类销售贡献</span><div class="erp-muted mt-1">按品类聚合的近 7 日销售额</div></div>
                <div class="card-body pt-1"><div class="erp-category-chart">{!! $categoryChart !!}</div></div>
            </div>
        </div>
        <div class="col-xl-7">{!! $inventoryWarnings !!}</div>
    </div>

    <div class="row">
        <div class="col-xl-5">
            <div class="card erp-dashboard-card">
                <div class="card-header"><span class="card-box-title">快捷操作</span><div class="erp-muted mt-1">用 Widget 与业务入口快速编排工作流</div></div>
                <div class="card-body">
                    <div class="erp-action-grid">
                        @foreach($data['quickActions'] as $action)
                            <div class="erp-action">
                                <div class="erp-action-icon bg-light-{{ $action['color'] }} text-{{ $action['color'] }}"><i class="{{ $action['icon'] }}"></i></div>
                                <strong>{{ $action['title'] }}</strong><span>{{ $action['description'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card erp-dashboard-card">
                <div class="card-header"><span class="card-box-title">待办中心</span><div class="erp-muted mt-1">优先处理重要事项</div></div>
                <div class="card-body py-2">
                    <ul class="erp-todo-list">
                        @foreach($data['todos'] as $todo)
                            <li class="erp-todo-item">
                                <i class="erp-todo-mark bg-{{ $todo['tone'] }}"></i>
                                <div class="erp-todo-body"><div class="erp-todo-title">{{ $todo['title'] }}</div><div class="erp-todo-meta">{{ $todo['meta'] }}</div></div>
                                <span class="erp-todo-priority erp-priority-{{ $todo['tone'] }}">{{ $todo['priority'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card erp-dashboard-card">
                <div class="card-header"><span class="card-box-title">动态追踪</span><div class="erp-muted mt-1">关键业务事件实时汇集</div></div>
                <div class="card-body">
                    @foreach($data['activities'] as $activity)
                        <div class="erp-activity">
                            <div class="erp-activity-icon bg-{{ $activity['color'] }}"><i class="{{ $activity['icon'] }}"></i></div>
                            <div><div class="erp-activity-title">{{ $activity['title'] }}</div><div class="erp-activity-content">{{ $activity['content'] }}</div><span class="erp-activity-time">{{ $activity['time'] }}</span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

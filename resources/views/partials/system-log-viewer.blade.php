@php
    $currentLevel = $level ?? '';
    $allLevels = ['ERROR' => 'danger', 'WARNING' => 'warning', 'INFO' => 'success', 'NOTICE' => 'info', 'DEBUG' => 'secondary', 'CRITICAL' => 'danger', 'ALERT' => 'dark', 'EMERGENCY' => 'dark'];
    $request = app('request');
    $dirParts = $dir ? explode('/', $dir) : [];
    $hasDirs = !$request->get('filename') && !empty($logDirs);
    $hasFiles = !empty($logFiles);
@endphp

<style>
.log-viewer-wrapper { padding: 0; }
.log-viewer-sidebar {
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    height: calc(100vh - 200px);
    overflow-y: auto;
    position: sticky;
    top: 15px;
}
.log-viewer-sidebar .lv-header {
    padding: 15px 15px 10px;
    border-bottom: 1px solid #f0f0f0;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 10;
}
.log-viewer-sidebar .breadcrumb-log {
    font-size: 13px;
    color: #555;
    margin-bottom: 10px;
    word-break: break-all;
}
.log-viewer-sidebar .breadcrumb-log a { color: var(--primary, #21b978); }
.log-viewer-sidebar .breadcrumb-log a:hover { text-decoration: underline; }
.log-viewer-sidebar .lv-search-box { position: relative; }
.log-viewer-sidebar .lv-search-box input {
    padding-right: 30px;
    border-radius: 20px;
    font-size: 13px;
}
.log-viewer-sidebar .lv-search-box .search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
}

/* 统一的列表样式 - 目录和文件都在同一列展示 */
.log-viewer-sidebar .nav-stacked {
    list-style: none;
    padding: 0;
    margin: 0;
}
.log-viewer-sidebar .nav-stacked > li {
    margin: 0;
    padding: 0;
}
.log-viewer-sidebar .nav-stacked > li > a {
    display: block;
    border-radius: 0;
    margin: 0;
    padding: 9px 15px 9px 18px;
    font-size: 13px;
    color: #555;
    transition: all 0.15s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-left: 3px solid transparent;
    border-bottom: 1px solid #f5f5f5;
}
.log-viewer-sidebar .nav-stacked > li > a:hover {
    background: #f5f5f5;
    color: var(--primary, #21b978);
    border-left-color: var(--primary, #21b978);
}
.log-viewer-sidebar .nav-stacked > li.active > a,
.log-viewer-sidebar .nav-stacked > li.dir-item.active > a {
    background: rgba(33, 185, 120, 0.08);
    color: var(--primary, #21b978);
    font-weight: 600;
    border-left-color: var(--primary, #21b978);
}
.log-viewer-sidebar .nav-stacked > li.dir-item > a {
    color: #666;
    font-weight: 500;
}
.log-viewer-sidebar .nav-stacked > li.dir-item > a:hover {
    color: var(--primary, #21b978);
}
/* 分组标题 */
.log-viewer-sidebar .group-title {
    padding: 10px 15px 6px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #aaa;
    font-weight: 700;
    background: #fafafa;
    border-bottom: 1px solid #f0f0f0;
    border-top: 1px solid #f0f0f0;
}
.log-viewer-sidebar .group-title:first-child { border-top: 0; }

.log-viewer-content {
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.log-viewer-content .lv-toolbar {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.log-viewer-content .lv-toolbar .btn { border-radius: 4px; }
.log-viewer-content .lv-toolbar .search-form { display: inline-block; }
.log-viewer-content .lv-toolbar .search-form input {
    border-radius: 20px;
    font-size: 13px;
    padding-left: 12px;
    padding-right: 30px;
    min-width: 200px;
}
.log-viewer-content .lv-toolbar .level-filter select {
    border-radius: 4px;
    font-size: 13px;
    padding: 4px 8px;
    border: 1px solid #ddd;
}
.log-viewer-content .lv-toolbar .pagination-btns { margin-left: auto; }

.log-stats-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 8px 15px;
    background: #fafafa;
    border-bottom: 1px solid #f0f0f0;
}
.log-stats-bar .stat-item {
    display: inline-flex;
    align-items: center;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
}
.log-stats-bar .stat-item:hover { opacity: 0.85; }
.log-stats-bar .stat-item .count {
    font-weight: 700;
    margin-left: 4px;
}

.log-table-wrapper { overflow-x: auto; }
.log-table-wrapper table { margin-bottom: 0; font-size: 13px; }
.log-table-wrapper thead th {
    background: #fafafa;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    color: #555;
    padding: 10px 12px;
    white-space: nowrap;
}
.log-table-wrapper tbody tr.log-row { border-bottom: 1px solid #f5f5f5; }
.log-table-wrapper tbody tr.log-row:hover { background: #fafcff; }
.log-table-wrapper tbody td { padding: 8px 12px; vertical-align: middle; }
.log-table-wrapper .log-index {
    color: #bbb;
    font-size: 12px;
    width: 40px;
    text-align: center;
}
.log-table-wrapper .log-level-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.log-table-wrapper .log-level-badge i { margin-right: 4px; }
.log-table-wrapper .log-env {
    font-weight: 600;
    color: #888;
    font-size: 12px;
}
.log-table-wrapper .log-time {
    color: #999;
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 12px;
    white-space: nowrap;
}
.log-table-wrapper .log-info {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 12px;
    color: #444;
    word-break: break-word;
    max-width: 500px;
}
.log-table-wrapper .log-action .btn { padding: 2px 8px; font-size: 11px; }

.log-table-wrapper tr.trace-row > td {
    padding: 0;
    border-top: 0;
}
.trace-dump {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px 20px;
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 12px;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-word;
    border-radius: 0 0 4px 4px;
    max-height: 500px;
    overflow-y: auto;
}
.trace-dump .trace-file { color: #4fc1ff; }
.trace-dump .trace-line { color: #ce9178; }
.trace-dump .trace-class { color: #4ec9b0; }
.trace-dump .trace-function { color: #dcdcaa; }

.lv-footer {
    padding: 10px 15px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #999;
}
.lv-footer .file-info span { margin-right: 12px; }
.lv-footer .file-info strong { color: #666; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}
.empty-state i { font-size: 48px; color: #ddd; margin-bottom: 15px; display: block; }
.empty-state p { font-size: 14px; margin: 0; }

.bg-danger { background-color: #f5365c !important; color: #fff; }
.bg-warning { background-color: #fb6340 !important; color: #fff; }
.bg-info { background-color: #11cdef !important; color: #fff; }
.bg-success { background-color: #2dce89 !important; color: #fff; }
.bg-secondary { background-color: #f4f5f7 !important; color: #8898aa; }
.bg-dark { background-color: #172b4d !important; color: #fff; }

@media (max-width: 768px) {
    .log-viewer-sidebar { position: static; height: auto; max-height: 300px; margin-bottom: 15px; }
    .log-viewer-content .lv-toolbar { flex-direction: column; align-items: stretch; }
    .log-viewer-content .lv-toolbar .pagination-btns { margin-left: 0; }
    .log-table-wrapper .log-info { max-width: 250px; }
}
</style>

<div class="wrapper log-viewer-wrapper">
    <div class="row">
        {{-- 侧边栏 --}}
        <div class="col-md-3 col-lg-2">
            <div class="log-viewer-sidebar">
                <div class="lv-header">
                    <div class="breadcrumb-log">
                        <i class="fa fa-folder-open-o"></i>
                        <a href="{{ url('/admin/auth/system-log-viewer') }}">logs</a>
                        @foreach($dirParts as $i => $v)
                            @php
                                $partDir = implode('/', array_slice($dirParts, 0, $i + 1));
                            @endphp
                            / <a href="{{ url('/admin/auth/system-log-viewer') }}?dir={{ $partDir }}">{{ $v }}</a>
                        @endforeach
                    </div>

                    <form action="{{ url('/admin/auth/system-log-viewer') }}" method="get" class="lv-search-box">
                        <div class="input-group input-group-sm" style="width: 100%">
                            <input name="filename" class="form-control" value="{{ $request->get('filename') }}" type="text" placeholder="搜索文件名..." />
                            <span class="search-icon"><i class="fa fa-search"></i></span>
                        </div>
                    </form>
                </div>

                <div class="box-body no-padding">
                    <ul class="nav nav-pills nav-stacked">
                        {{-- 目录分组 --}}
                        @if($hasDirs)
                            <li class="group-title"><i class="fa fa-folder"></i> 目录</li>
                            @foreach($logDirs as $d)
                                @php
                                    $dirActive = ($d === $fileName) ? 'active' : '';
                                @endphp
                                <li class="dir-item {{ $dirActive }}">
                                    <a href="{{ url('/admin/auth/system-log-viewer') }}?dir={{ $d }}">
                                        <i class="fa fa-folder-o"></i>&nbsp;{{ basename($d) }}
                                    </a>
                                </li>
                            @endforeach
                        @endif

                        {{-- 文件分组 --}}
                        @if($hasFiles)
                            <li class="group-title"><i class="fa fa-file-text-o"></i> 文件</li>
                            @foreach($logFiles as $log)
                                @php
                                    $fileActive = $log['active'] ? 'active' : '';
                                    $fileIcon = $log['active'] ? 'fa-file-text' : 'fa-file-text-o';
                                @endphp
                                <li class="{{ $fileActive }}" title="{{ $log['file'] }}">
                                    <a href="{{ $log['url'] }}">
                                        <i class="fa {{ $fileIcon }}"></i>&nbsp;{{ $log['file'] }}
                                    </a>
                                </li>
                            @endforeach
                        @endif

                        @if(!$hasDirs && !$hasFiles)
                            <li class="group-title">无内容</li>
                            <li><a href="javascript:void(0)" style="color:#ccc;">暂无日志文件</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        {{-- 主内容 --}}
        <div class="col-md-9 col-lg-10">
            <div class="log-viewer-content">
                {{-- 工具栏 --}}
                <div class="lv-toolbar">
                    @if($filePath && is_file($filePath))
                        <a href="{{ $downloadUrl ?? '#' }}" class="btn btn-sm btn-default" title="下载日志文件">
                            <i class="fa fa-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-default" id="btn-clear-log" title="清空文件内容">
                            <i class="fa fa-eraser"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete-log" title="删除日志文件">
                            <i class="fa fa-trash-o"></i>
                        </button>
                    @endif
                    &nbsp;
                    {{-- 关键词搜索 --}}
                    <form action="{{ $request->fullUrlWithQuery(['keyword' => null, 'offset' => null]) }}" class="search-form" method="get">
                        <input type="hidden" name="dir" value="{{ $dir }}">
                        <input type="hidden" name="filename" value="{{ $request->get('filename') }}">
                        <input type="hidden" name="level" value="{{ $currentLevel }}">
                        <div class="input-group input-group-sm">
                            <input name="keyword" class="form-control" value="{{ $request->get('keyword') }}" type="text" placeholder="搜索日志内容..." />
                            <span class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                            </span>
                        </div>
                    </form>

                    {{-- 级别筛选 --}}
                    <div class="level-filter">
                        <form method="get" id="level-filter-form" style="display:inline;">
                            <input type="hidden" name="dir" value="{{ $dir }}">
                            <input type="hidden" name="filename" value="{{ $request->get('filename') }}">
                            <select name="level" class="form-control form-control-sm" onchange="document.getElementById('level-filter-form').submit();">
                                <option value="">全部级别</option>
                                @foreach($allLevels as $lv => $color)
                                    @php
                                        $selected = ($currentLevel === $lv) ? 'selected' : '';
                                    @endphp
                                    <option value="{{ $lv }}" {{ $selected }}>{{ $lv }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="pagination-btns">
                        <div class="btn-group btn-group-sm">
                            @if ($prevUrl)
                                <a href="{{ $prevUrl }}" class="btn btn-default" title="上一页"><i class="fa fa-chevron-left"></i></a>
                            @endif
                            @if ($nextUrl)
                                <a href="{{ $nextUrl }}" class="btn btn-default" title="下一页">下一页 <i class="fa fa-chevron-right"></i></a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 统计栏 --}}
                @if(!empty($levelStats))
                    <div class="log-stats-bar">
                        @foreach($levelStats as $lv => $count)
                            @php
                                $statColor = $levelColors[$lv] ?? 'secondary';
                                $statIcon = $levelIcons[$lv] ?? 'fa-file';
                                $statLevel = ($lv === 'NONE') ? null : $lv;
                            @endphp
                            <a href="{{ $request->fullUrlWithQuery(['level' => $statLevel, 'offset' => null]) }}" class="stat-item bg-{{ $statColor }}">
                                <i class="fa {{ $statIcon }}"></i> {{ $lv }} <span class="count">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- 日志表格 --}}
                <div class="log-table-wrapper">
                    @if(empty($logs))
                        <div class="empty-state">
                            <i class="fa fa-inbox"></i>
                            <p>暂无日志记录</p>
                        </div>
                    @else
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="log-index">#</th>
                                    <th>级别</th>
                                    <th>Env</th>
                                    <th>内容</th>
                                    <th>时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $index => $log)
                                    @php
                                        $lvColor = $levelColors[strtoupper($log['level'] ?? '')] ?? 'secondary';
                                        $lvIcon = $levelIcons[strtoupper($log['level'] ?? '')] ?? 'fa-file';
                                        $hasTrace = !empty($log['trace']);
                                        $logLevelText = $log['level'] ?: 'LOG';
                                    @endphp
                                    <tr class="log-row">
                                        <td class="log-index">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="log-level-badge bg-{{ $lvColor }}">
                                                <i class="fa {{ $lvIcon }}"></i>{{ $logLevelText }}
                                            </span>
                                        </td>
                                        <td><span class="log-env">{{ $log['env'] }}</span></td>
                                        <td><div class="log-info">{{ $log['info'] }}</div></td>
                                        <td><span class="log-time">{{ $log['time'] }}</span></td>
                                        <td class="log-action">
                                            @if($hasTrace)
                                                <button class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target=".trace-{{ $index }}">
                                                    <i class="fa fa-bug"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>

                                    @if($hasTrace)
                                        <tr class="collapse trace-row trace-{{ $index }}">
                                            <td colspan="6">
                                                <div class="trace-dump">{{ $log['trace'] }}</div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- 底部信息 --}}
                @if($filePath && is_file($filePath))
                    <div class="lv-footer">
                        <div class="file-info">
                            <span><strong>文件:</strong> {{ basename($filePath) }}</span>
                            <span><strong>大小:</strong> {{ $size }}</span>
                            @if(!empty($modifiedTime))
                                <span><strong>更新:</strong> {{ date('Y-m-d H:i:s', $modifiedTime) }}</span>
                            @endif
                            <span><strong>记录:</strong> {{ count($logs) }} 条</span>
                        </div>
                        <div class="btn-group btn-group-sm">
                            @if ($prevUrl)
                                <a href="{{ $prevUrl }}" class="btn btn-default"><i class="fa fa-chevron-left"></i> 上一页</a>
                            @endif
                            @if ($nextUrl)
                                <a href="{{ $nextUrl }}" class="btn btn-default">下一页 <i class="fa fa-chevron-right"></i></a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    var deleteUrl = "{{ admin_route('log-viewer.delete') }}";
    var clearUrl  = "{{ admin_route('log-viewer.clear') }}";
    var file      = "{{ $fileName ?? '' }}";
    var dir       = "{{ $dir ?? '' }}";

    $('#btn-delete-log').off('click').on('click', function () {
        if (!confirm('确定要删除此日志文件吗？此操作不可恢复！')) return;

        $.ajax({
            url: deleteUrl,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') || LA.token, file: file, dir: dir },
            success: function (res) {
                if (res.status) {
                    Dcat.success(res.message);
                    setTimeout(function(){ location.href = "{{ url('/admin/auth/system-log-viewer') }}"; }, 1000);
                } else {
                    Dcat.error(res.message || '删除失败');
                }
            },
            error: function () { Dcat.error('请求失败'); }
        });
    });

    $('#btn-clear-log').off('click').on('click', function () {
        if (!confirm('确定要清空此日志文件的内容吗？')) return;

        $.ajax({
            url: clearUrl,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') || LA.token, file: file, dir: dir },
            success: function (res) {
                if (res.status) {
                    Dcat.success(res.message);
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    Dcat.error(res.message || '清空失败');
                }
            },
            error: function () { Dcat.error('请求失败'); }
        });
    });
});
</script>
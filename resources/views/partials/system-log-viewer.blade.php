<div class="wrapper">
    <div class="row">
        <div class="col-md-2">
            <div class="">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-folder-open-o"></i>
                        <a href="{{ url('/admin/ycookies/log-viewer') }}">logs</a>
                        @if($dir)
                            @php($tmp = '')
                            @foreach(explode('/', $dir) as $v)
                                @php($tmp .= '/'.$v)
                                /
                                <a href="{{ url('/admin/ycookies/log-viewer') }}?dir={{trim($tmp, '/')}}">{{ $v }}</a>
                            @endforeach
                        @endif
                    </h3>
                </div>

                <form action="{{ url('/admin/ycookies/log-viewer') }}" style="display: inline-block;padding-left: 15px">
                    <div class="input-group-sm" style="display: inline-block;width: 100%">
                        <input name="filename" class="form-control" value="{{ app('request')->get('filename') }}" type="text" placeholder="Search..." />
                    </div>
                </form>

                <div class="box-body no-padding">
                    <ul class="nav nav-pills nav-stacked">
                        @if(! app('request')->get('filename'))
                            @foreach($logDirs as $d)
                                <li @if($d === $fileName) class="active" @endif>
                                    <a class="dir" href="{{ url('/admin/ycookies/log-viewer') }}?dir= {{$d }}">
                                        <i class="fa fa-folder-o"></i>{{ basename($d) }}
                                    </a>
                                </li>
                            @endforeach
                        @endif

                        @foreach($logFiles as $log)
                            <li @if($log['active'])class="active"@endif>
                                <a href="{{ $log['url'] }}">
                                    <i class="fa fa-file-text{{ ($log['active']) ? '' : '-o' }}"></i>{{ $log['file'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <!-- /.box-body -->
            </div>

            <!-- /.box -->
        </div>
        <!-- /.col -->

        <!-- /.col -->
        <div class="col-md-10">
            <div class="box box-primary">
                <div class="box-header with-border">
                    {{--<a href="{{ admin_route('ycookies.log-viewer.download', ['dir' => $dir, 'file' => $fileName, 'filename' => app('request')->get('filename')]) }}" class="btn btn-primary btn-sm download" style="color: #fff"><i class="fa-download fa"></i> 下载</a>
                    --}}<button class="btn btn-default btn-sm download"><i class="fa-trash-o fa"></i> 删除 </button>
                    &nbsp;
                    <form action="{{ app('request')->fullUrlWithQuery(['keyword' => null,]) }}" style="display: inline-block;width: 180px">
                        <div class="input-group-sm" style="display: inline-block;width: 100%">
                            <input type="hidden" name="dir" value="{{ $dir }}">
                            <input type="hidden" name="filename" value="{{ app('request')->get('filename') }}">
                            <input name="keyword" class="form-control" value="{{ app('request')->get('keyword') }}" type="text" placeholder="查找..." />
                        </div>
                    </form>
                    <div class="float-right">
                        {{--<a class=""><strong>Size:</strong> {{ $size }} &nbsp; <strong>Updated at:</strong>
                            {{ date('Y-m-d H:i:s', filectime($filePath)) }}</a>--}}
                        &nbsp;
                        <div class="btn-group">
                            @if ($prevUrl)
                                <a href="{{ $prevUrl }}" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i> Previous</a>
                            @endif
                            @if ($nextUrl)
                                <a href="{{ $nextUrl }}" class="btn btn-default btn-sm">Next <i class="fa fa-chevron-right"></i></a>
                            @endif
                        </div>
                        <!-- /.btn-group -->
                    </div>
                    <!-- /.box-tools -->
                </div>
                <!-- /.box-header -->
                <div class="box-body no-padding">

                    <div class="table-responsive">
                        <table class="table table-hover">

                            <thead>
                            <tr>
                                <th></th>
                                <th>级别</th>
                                <th>Env</th>
                                <th>时间</th>
                                <th>内容</th>
                                <th></th>
                            </tr>
                            </thead>

                            <tbody>

                            @foreach($logs as $index => $log)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="label bg-{{\Dcat\Admin\LogViewer\Http\Controllers\LogViewer::$levelColors[$log['level']]}}">{{ $log['level'] }}</span></td>
                                    <td><strong>{{ $log['env'] }}</strong></td>
                                    <td><pre>{{ $log['info'] }}</pre></td>
                                    <td style="width:150px;">{{ $log['time'] }}</td>
                                    <td style="width:80px;">
                                        @if(!empty($log['trace']))
                                            <button class="btn btn-primary btn-sm" data-toggle="collapse" data-target=".trace-{{$index}}">查看</button>
                                        @endif
                                    </td>
                                </tr>

                                @if (!empty($log['trace']))
                                    <tr class="collapse trace-{{$index}}">
                                        <td colspan="6"><div class="trace-dump">{{ $log['trace'] }}</div></td>
                                    </tr>
                                @endif

                            @endforeach

                            </tbody>
                        </table>
                        <!-- /.table -->
                    </div>


                </div>
                <div class="box-footer">
                    <div class="float-left">
                        <a class=""><strong>Size:</strong> {{ $size }} &nbsp; <strong>Updated at:</strong>
                            {{ \Carbon\Carbon::create(date('Y-m-d H:i:s', filectime($filePath)))->diffForHumans() }}</a>
                    </div>
                    <div class="float-right">
                        <div class="btn-group">
                            @if ($prevUrl)
                                <a href="{{ $prevUrl }}" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i> Previous</a>
                            @endif
                            @if ($nextUrl)
                                <a href="{{ $nextUrl }}" class="btn btn-default btn-sm">Next <i class="fa fa-chevron-right"></i></a>
                            @endif
                        </div>
                        <!-- /.btn-group -->
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <!-- /. box -->
        </div>

    </div>
</div>
<div class="descriptions-box">
@if($card === true)

        <div class="card {{$shadow}}">
            @if(isset($header))
                <div class="box-header with-border">
                    {!! $header !!}
                </div>
            @endif

            <div class="card-body" style="padding:1rem !important;">
                <div class="descriptions-{{ $columns ?? 1 }}-columns">
                    @foreach($fields as $key => $field)
                        @php $field_data =  $field['element']->getVariables() @endphp
                        <div class="descriptions-row @if(isset($field_data['dedicated_line']) && $field_data['dedicated_line'] === true) dedicated-line @endif">
                            <div class="descriptions-label"
                                 style="justify-content:{{$label_justify_content}};min-width:{{$label_width}};max-width:{{$label_width}}"
                                 >{{ $field_data['label'] }}   {!! $field_data['help'] !!}
                            </div>
                            <div class="descriptions-content"
                                 style="justify-content:{{$content_justify_content}};{{$content_white_space}}"
                                 data-title="{{ is_string($field_data['content']) ? $field_data['content'] : '' }}">
                                @if($field_data['escape'])
                                    {{ $field_data['content'] }}
                                @else
                                    {!! $field_data['content'] !!}
                                @endif
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
            @foreach($fields as $key => $field)
                @php $field_data =  $field['element']->getVariables() @endphp
                <div class="descriptions-row @if(isset($field_data['dedicated_line']) && $field_data['dedicated_line'] === true) dedicated-line @endif">
                    <div class="descriptions-label"
                         style="justify-content:{{$label_justify_content}};min-width:{{$label_width}};max-width:{{$label_width}}"
                    >{{ $field_data['label'] }}   {!! $field_data['help'] !!}
                    </div>
                    <div class="descriptions-content"
                         style="justify-content:{{$content_justify_content}};{{$content_white_space}}"
                         data-title="{{ is_string($field_data['content']) ? $field_data['content'] : '' }}">
                        @if($field_data['escape'])
                            {{ $field_data['content'] }}
                        @else
                            {!! $field_data['content'] !!}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
    <style>
        .descriptions-box{
            margin-bottom: 10px;
        }
        .descriptions-1-columns,
        .descriptions-2-columns,
        .descriptions-3-columns,
        .descriptions-4-columns,
        .descriptions-5-columns,
        .descriptions-6-columns {
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
            padding: 12px;
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
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 5px 10px;
            /*padding-left: 10px;
            padding-right: 10px;*/
            display: flex;
            align-items: center;
            justify-content: flex-start;
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

            .descriptions-4-columns .descriptions-row:not(.dedicated-line) {
                width: 25%;
                float: left;
                border-right: 1px solid #f0f0f0;
            }

            .descriptions-5-columns .descriptions-row:not(.dedicated-line) {
                width: 20%;
                float: left;
                border-right: 1px solid #f0f0f0;
            }

            .descriptions-6-columns .descriptions-row:not(.dedicated-line) {
                width: 16.66%;
                float: left;
                border-right: 1px solid #f0f0f0;
            }
        }

        @media screen and (max-width: 767px) {
            .descriptions-2-columns .descriptions-row,
            .descriptions-3-columns .descriptions-row,
            .descriptions-4-columns .descriptions-row,
            .descriptions-5-columns .descriptions-row,
            .descriptions-6-columns .descriptions-row {
                width: 100%;
                float: none;
                border-right: none;
            }
        }

        /* Clear float */
        .descriptions-2-columns::after,
        .descriptions-3-columns::after,
        .descriptions-4-columns::after,
        .descriptions-5-columns::after,
        .descriptions-6-columns::after {
            content: '';
            display: table;
            clear: both;
        }

        /* 最后一行的右边框处理 */
        @media screen and (min-width: 768px) {
            .descriptions-2-columns .descriptions-row:not(.dedicated-line):nth-child(2n),
            .descriptions-3-columns .descriptions-row:not(.dedicated-line):nth-child(3n),
            .descriptions-4-columns .descriptions-row:not(.dedicated-line):nth-child(4n),
            .descriptions-5-columns .descriptions-row:not(.dedicated-line):nth-child(5n),
            .descriptions-6-columns .descriptions-row:not(.dedicated-line):nth-child(6n) {
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

<div class="fieldset-box">
    <fieldset>
        @if(!empty($title)) <legend>{{$title}}</legend>@endif
        <div class="infolist">
            <div class="row">
                @foreach($fields as $key => $field)
                    @php $field_data =  $field['element']->getVariables()@endphp
                    @if(isset($field_data['dedicated_line']) && $field_data['dedicated_line'] === true)
                        <div class="col-md-12">
                    @else
                <div class="col-md-{{ $columns ?? 1 }}">
                    @endif
                    <dl>
                        <dt>{{ $field_data['label'] }}   {!! $field_data['help'] !!}</dt>
                        <dd>@if($field_data['escape'])
                                {{ $field_data['content'] }}
                            @else
                                {!! $field_data['content'] !!}
                            @endif</dd>

                    </dl>
                </div>

                @endforeach

            </div>
        </div>

    </fieldset>
</div>
<style>

    /* Fieldset 样式 */
    .fieldset-box fieldset {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        background-color: #ffffff;
        {{$shadow}}
    }

    /* Legend 样式 */
    .fieldset-box legend {
        font-size: 1.2em;
        font-weight: bold;
        color: #585858;
        padding: 0 10px;
        background-color: #ffffff;
        border-radius: 5px;
    }
    /* 按钮样式 */
    .fieldset-box button {
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
    }

    .fieldset-box button:hover {
        background-color: #0056b3;
    }

    /* 响应式布局 */
    .fieldset-box  @media (max-width: 600px) {
        fieldset {
            padding: 10px;
        }
    }
    .infolist dt {
        font-size: 14px;
        margin-bottom:5px;
    }

    .infolist dd {
        margin-bottom: 10px;
        color: #99a8b0 !important;
        {{$content_white_space}}
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .infolist .card-footer {
        margin: 2px;
    }
</style>
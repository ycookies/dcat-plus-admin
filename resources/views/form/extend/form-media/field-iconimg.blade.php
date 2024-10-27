<div data-field="iconimg" class="lake-form-media lake-form-field-iconimg lake-form-media-{{ str_replace(['[', ']'], ['-', ''], $name) }} {{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}"
    data-name="{{ str_replace(['[', ']'], ['-', ''], $name) }}" 
    data-options="{{ json_encode($options) }}" 
>
    <label class="{{$viewClass['label']}} control-label">{{ $label }}</label>
    
    <div class="{{$viewClass['field']}}">

        <div class="lake-form-media-img-show" style="display: none;">
            <div class="row lake-form-media-img-show-row" >
            </div>
        </div>

        @include('admin::form.error')
        
        <div class="input-group">
            
            <input type="text" 
                name="{{$name}}" 
                value="{{ old($column, $value)?(is_array(old($column, $value))?json_encode(old($column, $value)):old($column, $value)):'' }}" 
                class="form-control lake-form-media-input {{$class}}"  
                placeholder="{{ $placeholder }}" 
                @if(!$options['remove']) readonly="readonly" @endif
                {!! $attributes !!} />

            <div class="input-group-btn input-group-append">
                <div tabindex="500" 
                    class="btn btn-primary btn-file lake-form-media-btn-file" type="button" 
                    data-target="LakeFormMediaModel{{ str_replace(['[', ']'], ['-', ''], $name) }}" 
                    data-title="{{ $label }}" 
                    data-token="{{ csrf_token() }}"
                >
                    <i class="fa fa-folder-open"></i>&nbsp;  
                    <span class="hidden-xs">{{ admin_trans('form-media.select') }}</span>
                </div>
            </div>

        </div><!-- /input-group -->
        
        @include('admin::form.help-block')
    </div>
    
    <!-- 弹窗 -->
    <div id="LakeFormMediaModel{{ str_replace(['[', ']'], ['-', ''], $name) }}Body" >
        <div class="modal fade lake-form-media-modal" 
            tabindex="-1" 
            role="dialog" 
            data-idkey="{{ str_replace(['[', ']'], ['-', ''], $name) }}"
            data-media="lake-form-media-{{ str_replace(['[', ']'], ['-', ''], $name) }}"
            aria-labelledby="LakeFormMediaModalLabel"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content"  style="width: 100%">
                  <div class="modal-header">
                    <h4 class="modal-title" id="LakeFormMediaModalLabel">{{ admin_trans('form-media.check_image') }}</h4>
                    <button type="button" 
                        class="close lake-form-media-close" 
                        data-modal="LakeFormMediaModel{{ str_replace(['[', ']'], ['-', ''], $name) }}" 
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="mailbox-controls with-border lake-form-media-actions-label" style="margin-left: 10px;">
                        <label class="btn btn-primary lake-form-media-upload-label">
                            <i class="fa fa-upload"></i>&nbsp;&nbsp;{{ admin_trans('form-media.upload') }}
                            <input type="file" class="hidden file-upload lake-form-media-upload" multiple="" accept="{{ $options['accept'] }}" />
                        </label>
                        
                        <label class="btn btn-light lake-form-media-modal-order" 
                            data-order="time"
                            title="{{ admin_trans('form-media.change_sort') }}"
                        >
                            <i class="fa fa-calendar-times-o"></i>
                        </label>
                        
                        <div class="input-group pull-right lake-form-media-create-folder-label" style="width: 250px;">
                            <input type="text" class="form-control lake-form-media-dir-input" value="">
                            <div class="input-group-btn input-group-append">
                                <button type="button" class="btn btn-primary lake-form-media-dir-button">
                                    <i class="fa fa-folder"></i>&nbsp;&nbsp;{{ admin_trans('form-media.create') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body pre-scrollable" >
                        <!-- 页面导航 -->
                        <ol class="breadcrumb lake-form-media-nav-ol" data-current-path="/" style="margin-bottom: 10px;">
                            <li class="breadcrumb-item lake-form-media-nav-li">
                                加载中...
                            </li>
                        </ol>
                        
                        <!-- 图片 -->
                        <div class="row lake-form-media-body-table">
                            <!-- js 加载 -->
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default lake-form-media-close" data-modal="LakeFormMediaModel{{ str_replace(['[', ']'], ['-', ''], $name) }}">{{ admin_trans('form-media.close') }}</button>
                        <button type="button" class="btn btn-primary lake-form-media-submit">{{ admin_trans('form-media.save') }}</button>
                    </div>
                    
                    <!-- 分页 -->
                    <div class="lake-form-media-modal-page"
                        data-current-page="1"
                        data-page-size="{{ $options['pagesize'] }}"
                        data-total-page="0"
                    >
                        <button type="button" class="btn btn-primary hidden lake-form-media-modal-prev-page">{{ admin_trans('form-media.prev') }}</button>
                        <button type="button" class="btn btn-primary hidden lake-form-media-modal-next-page">{{ admin_trans('form-media.next') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.lake-form-media-{{ str_replace(['[', ']'], ['-', ''], $name) }} .lake-form-media-input').each(function(i, cont) {
        LakeFormMedia.refreshInputPreview(cont);
    });
});
</script>

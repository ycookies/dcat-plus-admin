<style>
    [v-cloak] {
        display: none;
    }

    .diy-card {
        border-radius: .25rem;
        /*padding: 15px;*/
        /*margin: 5px;*/
        overflow: auto;
        position: relative;
    }

    .option-area > div:not(:last-child) {
        margin-bottom: 3px;
    }

    .preview-item {
        margin-top: 10px;
    }

    .the-mask {
        width: 98%;
        background: transparent;
        height: 92%;
        position: absolute;
        z-index: 999;
    }

    /*.diy-main .form-control{*/
    /*    width: 80%;*/
    /*}*/
</style>

<div class="diy-main" v-cloak>
    <div style="width: 100%; height: 100%; overflow: auto">
        <div class="diy-card">
            {{--            @if($show_mask)--}}
            {{--                <div class="the-mask" onclick="maskTips()"></div>--}}
            {{--            @endif--}}

            <div v-for="(item, index) in contents" :key="index">
                {{--自定义预览html--}}
                {!! $preview_html !!}

                {{--input--}}
                <div class="preview-item" v-if="item.type == 'input'" :class="'animate-item-' + index">
                    <label class="d-flex justify-content-between">
                        <div>
                            <span class="text-danger" v-if="item.required == 1">* </span>
                            @{{ item.label }}
                        </div>
                    </label>
                    <div class="">
                        <input type="text"
                               class="form-control"
                               :value="item.default_value"
                               disabled
                               :placeholder="`请输入${item.label}`">
                    </div>
                </div>

                {{--textarea--}}
                <div class="preview-item" v-if="item.type == 'textarea'" :class="'animate-item-' + index">
                    <label class="d-flex justify-content-between">
                        <div>
                            <span class="text-danger" v-if="item.required == 1">* </span>
                            @{{ item.label }}
                        </div>
                    </label>
                    <div class="">
                        <textarea class="form-control"
                                  :rows="item.rows"
                                  disabled
                                  :value="item.default_value"></textarea>
                    </div>
                </div>

                {{--radio--}}
                <div class="preview-item" v-if="item.type == 'radio'" :class="'animate-item-' + index">
                    <label class="d-flex justify-content-between">
                        <div>
                            <span class="text-danger" v-if="item.required == 1">* </span>
                            @{{ item.label }}
                        </div>
                    </label>
                    <div class="">
                        <div class="custom-control custom-radio custom-control-inline"
                             v-for="(opt, opt_key) in item.options.values"
                             :key="opt_key">
                            <input type="radio"
                                   :id="'radio_item_' + index + opt_key"
                                   :name="'radio_' + index"
                                   :checked="opt == item.default_value"
                                   disabled
                                   class="custom-control-input">
                            <label class="custom-control-label" :for="'radio_item_' + index + opt_key">
                                @{{ opt }}
                            </label>
                        </div>
                    </div>
                </div>

                {{--checkbox--}}
                <div class="preview-item" v-if="item.type == 'checkbox'" :class="'animate-item-' + index">
                    <label class="d-flex justify-content-between">
                        <div>
                            <span class="text-danger" v-if="item.required == 1">* </span>
                            @{{ item.label }}
                        </div>
                    </label>
                    <div class="">
                        <div class="custom-control custom-checkbox custom-control-inline"
                             v-for="(opt, opt_key) in item.options.values"
                             :key="opt_key">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   :checked="item.default_value ? item.default_value.split(',').indexOf(opt) > -1 : ''"
                                   disabled
                                   :id="'checkbox_item_' + index + opt_key">
                            <label class="custom-control-label" :for="'checkbox_item_' + index + opt_key">
                                @{{ opt }}
                            </label>
                        </div>
                    </div>
                </div>

                {{--select--}}
                <div class="preview-item" v-if="item.type == 'select'" :class="'animate-item-' + index">
                    <label class="d-flex justify-content-between">
                        <div>
                            <span class="text-danger" v-if="item.required == 1">* </span>
                            @{{ item.label }}
                        </div>
                    </label>
                    <div class="">
                        <select class="form-control" disabled>
                            <option
                                    v-for="(opt, opt_key) in item.options.values"
                                    :selected="opt == item.default_value"
                                    :key="opt_key">
                                @{{ opt }}
                            </option>
                        </select>
                    </div>
                </div>

                {{--upload--}}
                <div class="preview-item"
                     v-if="item.type == 'upload-image' || item.type == 'upload-vedio'"
                     :class="'animate-item-' + index">
                    <label class="d-flex justify-content-between">
                        <div>
                            <span class="text-danger" v-if="item.required == 1">* </span>
                            @{{ item.label }}
                        </div>
                    </label>
                    <div class="">
                        <div v-if="item.type == 'upload-image'">
                            <img v-for="(vv, kk) in (item.default_value ? item.default_value.split(',') : [])" :key="kk" :src="vv" alt="" width="150px" height="150px" style="margin: 5px;">
                        </div>
                        <div v-else>
                            <video v-for="(vvv, kkk) in (item.default_value ? item.default_value.split(',') : [])" :key="kkk" width="320" height="240" controls>
                                <source :src="vvv" type="video/mp4">
                                您的浏览器不支持 video 标签。
                            </video>
                        </div>
                        {{--<button class="btn btn-default btn-sm" onclick="maskTips()">--}}
                        {{--    @{{ item.name }}--}}
                        {{--</button>--}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    Dcat.ready(function () {
        $('.diy-main').parents('.box').css('border', 0).css('box-shadow', '0 0')
        $('.diy-main').parents('.box-body').css('padding', 0)
        $('.diy-main').parents('.col-sm-8').css('border-left', '1px solid ' + '{{ Admin::color()->primary() }}')

        Dcat.init('.diy-main', function ($this, id) {
            new Vue({
                el: `#${id}`,
                data: {
                    contents: {!! $value !!}
                },
            })
        })
    })

    function maskTips() {
        Dcat.warning('暂不支持操作表单')
    }
</script>

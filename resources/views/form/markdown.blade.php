<style>
    .editormd-fullscreen {z-index: 99999999;}
    /* 全屏按钮悬浮到编辑器右上角，方便快速进入/退出全屏（不必在工具堆里找） */
    .editormd .editormd-toolbar ul {
        padding-right: 42px; /* 给角落按钮让位，避免遮挡最右侧工具 */
    }
    .md-fullscreen-corner {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 30;
        margin: 0;
        list-style: none;
        padding: 8px 12px;
        line-height: 1;
        background: #fff;
        border-left: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        box-shadow: -1px 2px 4px rgba(0, 0, 0, .06);
        cursor: pointer;
    }
    .md-fullscreen-corner a {
        color: #666;
    }
    .md-fullscreen-corner:hover {
        background: #f3f4f7;
    }
    .editormd.edui-dark .md-fullscreen-corner {
        background: #2d3248;
        border-color: #414d77;
    }
    .editormd.edui-dark .md-fullscreen-corner a {
        color: #b7c5ff;
    }
</style>

<div class="{{$viewClass['form-group']}}">

    <label class="{{$viewClass['label']}} control-label">{!! $label !!}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <div class="{{$class}}" {!! $attributes !!}>
            <textarea class="d-none" name="{{$name}}" placeholder="{{ $placeholder }}">{!! $value !!}</textarea>
        </div>

        @include('admin::form.help-block')

    </div>
</div>

<script first>
    var ele = window.Element;
    Dcat.eMatches = ele.prototype.matches ||
        ele.prototype.msMatchesSelector ||
        ele.prototype.webkitMatchesSelector;
</script>

<script require="@editor-md-form" init="{!! $selector !!}">
    var opts = {!! $options !!};
    var editor = editormd(id, opts);

    Element.prototype.matches = Dcat.eMatches;

    // CodeMirror 由 editor.md 异步加载；依赖已预载时 editormd 又会在构造返回前
    // 同步就绪，回调时序不可靠，这里轮询等待实例可用后再绑定粘贴上传
    if (!opts.readonly) {
        (function waitEditorReady(retries) {
            if (editor && editor.cm) {
                bindMarkdownPasteUpload(editor, opts);
                moveFullscreenToCorner(editor);
            } else if (retries > 0) {
                setTimeout(function () { waitEditorReady(retries - 1); }, 100);
            }
        })(50);
    }

    /**
     * 全屏按钮从工具栏移到编辑器右上角：editor.md 的工具栏点击是
     * 祖先级委托绑定，DOM 移动不影响事件触发；全屏状态下按钮随
     * 容器继续悬浮在右上角，可点它退出（ESC 依然有效）。
     */
    function moveFullscreenToCorner(editor) {
        // editormd 实例没有统一的容器属性，直接按初始化 id 取根容器
        var container = document.getElementById(id);
        if (! container) {
            return;
        }

        var icon = container.querySelector('[name="fullscreen"]');
        var li = icon && icon.closest('li');
        if (!li || li.classList.contains('md-fullscreen-corner')) {
            return;
        }

        li.classList.add('md-fullscreen-corner');
        container.appendChild(li);
    }

    /**
     * 粘贴图片自动上传：剪贴板里的图片文件走 imageUploadURL 上传，
     * 成功后把 ![](url) 插入光标处；纯文本粘贴不受影响。
     */
    function bindMarkdownPasteUpload(editor, opts) {
        var uploadUrl = opts.imageUploadURL;
        if (!uploadUrl) {
            return;
        }

        var cm = editor.cm;
        if (!cm) {
            return;
        }

        cm.getWrapperElement().addEventListener('paste', function (e) {
            var clipboard = e.clipboardData;
            if (!clipboard || !clipboard.items) {
                return;
            }

            var files = [];
            for (var i = 0; i < clipboard.items.length; i++) {
                var item = clipboard.items[i];
                if (item.kind === 'file' && item.type.indexOf('image/') === 0) {
                    var file = item.getAsFile();
                    if (file) {
                        files.push(file);
                    }
                }
            }
            if (!files.length) {
                return;
            }

            e.preventDefault();

            files.forEach(function (file, index) {
                var seq = files.length > 1 ? ' ' + (index + 1) : '';
                var loading = layer.msg('正在上传图片' + seq + '…', { time: 0 });
                var form = new FormData();
                form.append('editormd-image-file', file, file.name || ('paste-' + Date.now() + '.png'));

                // URL 已带 _token/disk/dir 参数，与工具栏图片上传同一接口
                fetch(uploadUrl, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    layer.close(loading);
                    if (data.success === 1 && data.url) {
                        var md = '![](' + data.url + ')\n';
                        if (editor.insertValue) {
                            editor.insertValue(md);
                        } else {
                            cm.replaceSelection(md);
                        }
                    } else {
                        Dcat.error(data.message || '图片上传失败');
                    }
                })
                .catch(function () {
                    layer.close(loading);
                    Dcat.error('图片上传失败');
                });
            });
        });
    }
</script>

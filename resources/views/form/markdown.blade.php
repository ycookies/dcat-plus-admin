<style>
    .editormd-fullscreen {z-index: 99999999;}
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
            } else if (retries > 0) {
                setTimeout(function () { waitEditorReady(retries - 1); }, 100);
            }
        })(50);
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

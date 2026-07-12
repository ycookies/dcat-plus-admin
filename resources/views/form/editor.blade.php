<div class="{{$viewClass['form-group']}}">

    <label class="{{$viewClass['label']}} control-label">{!! $label !!}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        {{--
            UEditor replaces the textarea with a div and copies its classes.  Do not
            apply Bootstrap's `form-control` here: its input-sized height would then
            constrain the UEditor holder and let the editor overflow subsequent fields.
        --}}
        <textarea class="{{$class}}" name="{{$name}}" placeholder="{{ $placeholder }}" {!! $attributes !!} >{{ $value }}</textarea>

        @include('admin::form.help-block')

    </div>
</div>

@php
    // UEditor 资源根路径，用于加载 lang / themes / dialogs。
    // 必须保证末尾带 "/"：admin_asset 会 trim 掉斜杠，而 UEditor 用 HOME_URL + 'themes/...' 拼接。
    // 用普通同步 script 设置 window.UEDITOR_HOME_URL，确保 ueditor.config.js 加载时（其顶层读取该全局变量）就能拿到正确路径。
    $ueditorHomeUrl = rtrim($homeUrl, '/') . '/';
@endphp
<script>
    window.UEDITOR_HOME_URL = '{!! $ueditorHomeUrl !!}';
</script>

<script require="@ueditor" init="{!! $selector !!}">
    var opts = {!! admin_javascript_json($options) !!};
    var enableDcatDarkMode = opts.dcatDarkMode !== false;
    delete opts.dcatDarkMode;
    opts.UEDITOR_HOME_URL = window.UEDITOR_HOME_URL;
    opts.initialFrameWidth = '100%';
    opts.serverHeaders = Object.assign({}, opts.serverHeaders || {}, {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    });

    var ue = UE.getEditor(id, opts);

    // 初始值回填（textarea 的 value 会被 UE 自动读取，这里兜底）
    ue.ready(function () {
        var initVal = $this.val();
        if (initVal) {
            ue.setContent(initVal);
        }

        if (enableDcatDarkMode) {
            var darkMode = window.DcatUeditorDarkMode;

            if (! darkMode) {
                darkMode = window.DcatUeditorDarkMode = (function () {
                    var editors = [];
                    var iframeThemeId = 'dcat-ueditor-dark-theme';
                    var iframeThemeUrl = window.UEDITOR_HOME_URL + 'themes/default/css/ueditor-dark-iframe.css';

                    function isDark() {
                        return document.body.classList.contains('dark-mode')
                            || document.documentElement.getAttribute('data-theme') === 'dark';
                    }

                    function setIframeTheme(iframe, dark) {
                        try {
                            var doc = iframe.contentDocument || iframe.contentWindow.document;

                            if (! doc || ! doc.documentElement) {
                                return;
                            }

                            doc.documentElement.classList.toggle('dcat-ueditor-dark', dark);

                            if (doc.body) {
                                doc.body.classList.toggle('dcat-ueditor-dark', dark);
                            }

                            if (! doc.getElementById(iframeThemeId) && doc.head) {
                                var link = doc.createElement('link');
                                link.id = iframeThemeId;
                                link.rel = 'stylesheet';
                                link.href = iframeThemeUrl;
                                doc.head.appendChild(link);
                            }
                        } catch (e) {
                            // Ignore an incomplete frame while it is loading.
                        }
                    }

                    function refresh() {
                        var dark = isDark();

                        editors.forEach(function (editor) {
                            if (editor.container) {
                                editor.container.classList.toggle('edui-dark', dark);
                            }

                            if (editor.iframe) {
                                setIframeTheme(editor.iframe, dark);
                            }
                        });

                        document.querySelectorAll('.edui-dialog, .edui-popup, .edui-menu, .edui-combox-menu')
                            .forEach(function (element) {
                                element.classList.toggle('edui-dark', dark);
                            });

                        document.querySelectorAll('.edui-dialog iframe')
                            .forEach(function (iframe) {
                                setIframeTheme(iframe, dark);
                            });
                    }

                    function observe() {
                        new MutationObserver(function (mutations) {
                            mutations.forEach(function (mutation) {
                                if (mutation.type === 'attributes' && mutation.target === document.body) {
                                    refresh();
                                    return;
                                }

                                for (var i = 0; i < mutation.addedNodes.length; i++) {
                                    var node = mutation.addedNodes[i];

                                    if (node.nodeType === 1 && (
                                        node.matches('.edui-dialog, .edui-popup, .edui-menu, .edui-combox-menu')
                                        || node.querySelector('.edui-dialog, .edui-popup, .edui-menu, .edui-combox-menu')
                                    )) {
                                        refresh();
                                        break;
                                    }
                                }
                            });
                        }).observe(document.body, {
                            attributes: true,
                            attributeFilter: ['class'],
                            childList: true,
                            subtree: true,
                        });

                        document.addEventListener('load', function (event) {
                            if (event.target.tagName === 'IFRAME') {
                                refresh();
                            }
                        }, true);
                    }

                    observe();

                    return {
                        register: function (editor) {
                            if (editors.indexOf(editor) === -1) {
                                editors.push(editor);
                            }

                            refresh();
                        },
                    };
                })();
            }

            darkMode.register(ue);
        }
    });

    // 内容变化同步回 textarea，保证表单提交拿到最新 HTML
    ue.addListener('contentChange', function () {
        $this.val(ue.getContent());
    });
</script>

<div class="role-page">
    <form id="role-blade-form"
          method="POST"
          action="{{ $editing ? admin_url('auth/roles/'.$role->getKey()) : admin_url('auth/roles') }}"
          autocomplete="off">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        {{-- <header class="role-page__hero">
            <div class="role-page__identity">
                <span class="role-page__hero-icon"><i class="feather icon-shield"></i></span>
                <div>
                    <div class="role-page__eyebrow">RBAC · {{ trans('admin.roles') }}</div>
                    <h1>{{ $editing ? trans('admin.edit') : trans('admin.create') }} {{ trans('admin.roles') }}</h1>
                </div>
            </div>
            <div class="role-page__hero-meta">
                <span class="role-page__mode-badge">
                    <i class="feather {{ $editing ? 'icon-edit-2' : 'icon-plus-circle' }}"></i>
                    {{ $editing ? trans('admin.edit') : trans('admin.create') }}
                </span>
                @if($editing)
                    <span class="role-page__id-badge">ID {{ $role->getKey() }}</span>
                @endif
            </div>
        </header> --}}

        @if($errors->any())
            <div class="role-page__errors">
                <span class="role-page__errors-icon"><i class="feather icon-alert-triangle"></i></span>
                <div>
                    <strong>{{ trans('admin.validation_error') }}</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <main class="role-page__content">
            <section class="role-panel role-panel--basic">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('admin.role_basic_information') }}</h3>
                    @if($editing)
                        <div class="role-panel__timestamps text-muted">
                            <span>{{ trans('admin.created_at') }} {{ $role->created_at }}</span>
                            <span class="ml-2">{{ trans('admin.updated_at') }} {{ $role->updated_at }}</span>
                        </div>
                    @endif
                </div>

                <div class="role-panel__body role-fields">
                    <div class="role-field">
                        <label for="role-slug">
                            {{ trans('admin.slug') }}
                            <span class="role-field__required">*</span>
                        </label>
                        <div class="role-field__control">
                            <span class="role-field__icon"><i class="feather icon-hash"></i></span>
                            <input id="role-slug"
                                   type="text"
                                   name="slug"
                                   maxlength="50"
                                   value="{{ old('slug', $role->slug) }}"
                                   class="{{ $errors->has('slug') ? 'is-invalid' : '' }}"
                                   {{ $isAdministrator ? 'readonly' : '' }}
                                   required>
                        </div>
                        @if($isAdministrator)
                            <div class="role-field__help"><i class="feather icon-lock"></i>{{ trans('admin.administrator_slug_readonly') }}</div>
                        @endif
                        @error('slug')<div class="role-field__error">{{ $message }}</div>@enderror
                    </div>

                    <div class="role-field">
                        <label for="role-name">
                            {{ trans('admin.name') }}
                            <span class="role-field__required">*</span>
                        </label>
                        <div class="role-field__control">
                            <span class="role-field__icon"><i class="feather icon-type"></i></span>
                            <input id="role-name"
                                   type="text"
                                   name="name"
                                   maxlength="50"
                                   value="{{ old('name', $role->name) }}"
                                   class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   required>
                        </div>
                        @error('name')<div class="role-field__error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <div class="role-page__authorization-grid {{ $bindMenu ? '' : 'role-page__authorization-grid--single' }}">
                <section class="role-panel">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ trans('admin.permissions') }}</h3>
                    </div>
                    <div class="role-panel__body role-panel__body--flush">
                        @include('admin::auth.partials.role-permissions', $permissionEditor)
                    </div>
                </section>

                @if($bindMenu)
                    <section class="role-panel role-panel--menu">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ trans('admin.menu') }}</h3>
                        </div>
                        <div class="role-panel__body role-panel__body--flush">
                            @include('admin::auth.partials.role-menus', $menuEditor)
                        </div>
                    </section>
                @endif
            </div>
        </main>

        <footer class="role-page__actions">
            <a href="{{ admin_url('auth/roles') }}" class="role-button role-button--secondary" data-pjax>
                <i class="feather icon-arrow-left"></i>{{ trans('admin.back') }}
            </a>
            <button type="submit" class="role-button role-button--primary role-submit-button">
                <i class="feather icon-save"></i>{{ trans('admin.submit') }}
            </button>
        </footer>
    </form>
</div>

<style>
    /* 设计令牌对齐 AdminLTE3/BS4 主题：边框 #f4f4f4、文字取 BS4 灰阶、
       主色随皮肤（由下方脚本经 Dcat.color 注入，默认主题 #586cb1）。
       partials（权限矩阵/菜单树）消费同一组变量，色调自动跟随统一。 */
    .role-page {
        --role-primary: #586cb1;
        --role-primary-dark: #4c60a3;
        --role-primary-soft: #e3e6f2;
        --role-surface: #ffffff;
        --role-surface-soft: #f8f9fa;
        --role-surface-hover: #f8f9fa;
        --role-border: #f4f4f4;
        --role-border-strong: #ced4da;
        --role-text: #212529;
        --role-muted: #6c757d;
        --role-danger: #ea5455;
        --role-danger-soft: #fdeceb;
        color: var(--role-text);
        font-size: 15px;
        line-height: 1.55;
    }

    body.dark-mode .role-page {
        --role-primary: #5b6fb3;
        --role-primary-dark: #4c60a3;
        --role-primary-soft: rgba(88, 108, 177, .18);
        --role-surface: #223;
        --role-surface-soft: #1c1c2e;
        --role-surface-hover: #2c2c43;
        --role-border: #303247;
        --role-border-strong: #3f4059;
        --role-text: #cacbd6;
        --role-muted: #a8a9bb;
        --role-danger: #f87171;
        --role-danger-soft: rgba(239, 82, 40, .14);
    }

    .role-page *, .role-page *::before, .role-page *::after { box-sizing: border-box; }

    .role-page__hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 22px 26px;
        margin-bottom: 16px;
        border: 1px solid var(--role-border);
        border-radius: .25rem;
        background: var(--role-surface);
        box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
    }

    .role-page__identity { display: flex; align-items: center; gap: 16px; min-width: 0; }
    .role-page__hero-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; flex: 0 0 52px;
        border-radius: .25rem; color: #fff;
        background: var(--role-primary);
        font-size: 23px;
    }
    .role-page__eyebrow { color: var(--role-primary); font-size: 12px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
    .role-page__hero h1 { margin: 2px 0 3px; color: var(--role-text); font-size: 23px; line-height: 1.3; font-weight: 750; }
    .role-page__hero p { margin: 0; color: var(--role-muted); font-size: 14px; }
    .role-page__hero-meta { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
    .role-page__mode-badge, .role-page__id-badge {
        display: inline-flex; align-items: center; gap: 6px;
        min-height: 34px; padding: 6px 12px; border-radius: .2rem;
        font-size: 13px; font-weight: 700;
    }
    .role-page__mode-badge { color: var(--role-primary); background: var(--role-primary-soft); }
    .role-page__id-badge { color: var(--role-muted); border: 1px solid var(--role-border); background: var(--role-surface); }

    .role-page__errors {
        display: flex; gap: 12px; padding: 14px 16px; margin-bottom: 16px;
        border: 1px solid rgba(234, 84, 85, .24); border-radius: .25rem;
        color: var(--role-danger); background: var(--role-danger-soft);
    }
    .role-page__errors-icon { font-size: 20px; }
    .role-page__errors strong { font-size: 15px; }
    .role-page__errors ul { margin: 4px 0 0; padding-left: 18px; }

    .role-page__content { display: grid; gap: 16px; }
    .role-page__authorization-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr); gap: 16px; align-items: start; }
    .role-page__authorization-grid--single { grid-template-columns: minmax(0, 1fr); }
    .role-panel {
        overflow: hidden;
        border: 1px solid var(--role-border);
        border-radius: .25rem;
        background: var(--role-surface);
        box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
    }
    /* 面板头直接用主题 .box-header.with-border + .box-title，不再自定义 */
    .role-panel .box-header .box-title { line-height: 30px; }
    .role-panel__timestamps { margin-left: auto; font-size: 12px; }
    .role-panel__body { padding: 18px; }
    .role-panel__body--flush { padding: 0; }

    .role-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .role-field label { display: block; margin-bottom: 7px; color: var(--role-text); font-size: 14px; font-weight: 700; }
    .role-field__required { color: var(--role-danger); }
    .role-field__control { position: relative; }
    .role-field__icon {
        position: absolute; z-index: 1; left: 14px; top: 50%; transform: translateY(-50%);
        color: var(--role-muted); font-size: 16px; pointer-events: none;
    }
    .role-field input {
        width: 100%; height: 40px; padding: 8px 14px 8px 42px;
        border: 1px solid var(--role-border-strong); border-radius: 5px;
        outline: none; color: var(--role-text); background: var(--role-surface);
        font-size: 15px; transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .role-field input:hover { border-color: var(--role-primary); }
    .role-field input:focus { border-color: var(--role-primary); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--role-primary) 25%, transparent); }
    .role-field input[readonly] { cursor: not-allowed; color: var(--role-muted); background: var(--role-surface-soft); }
    .role-field input.is-invalid { border-color: var(--role-danger); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--role-danger) 25%, transparent); }
    .role-field__help, .role-field__error { display: flex; align-items: center; gap: 5px; margin-top: 6px; font-size: 12.5px; }
    .role-field__help { color: var(--role-muted); }
    .role-field__error { color: var(--role-danger); }

    .role-page__actions {
        position: sticky; z-index: 20; bottom: 0;
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; padding: 13px 18px; margin-top: 16px;
        border: 1px solid var(--role-border); border-radius: .25rem;
        background: var(--role-surface);
        background: color-mix(in srgb, var(--role-surface) 92%, transparent);
        backdrop-filter: blur(12px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
    }
    .role-button {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-width: 110px; height: 34px; padding: 0 1.2rem;
        border: 1px solid transparent; border-radius: .2rem;
        font-size: 14px; font-weight: 700; text-decoration: none !important;
        cursor: pointer; box-shadow: 0 3px 1px -2px rgba(0, 0, 0, .065), 0 2px 2px 0 rgba(0, 0, 0, .065), 0 1px 5px 1px rgba(0, 0, 0, .065);
        transition: background .16s, border-color .16s;
    }
    .role-button--secondary { color: var(--role-text); border-color: var(--role-border); background: var(--role-surface); }
    .role-button--secondary:hover { color: var(--role-primary); border-color: var(--role-primary); }
    .role-button--primary { color: #fff; background: var(--role-primary); }
    .role-button--primary:hover { color: #fff; background: var(--role-primary-dark); }
    .role-button:disabled { cursor: wait; opacity: .7; }

    @media (max-width: 991px) {
        .role-page__authorization-grid { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 767px) {
        .role-page { font-size: 14px; }
        .role-page__hero { align-items: flex-start; padding: 18px; }
        .role-page__hero-meta { display: none; }
        .role-page__hero-icon { width: 44px; height: 44px; flex-basis: 44px; border-radius: .25rem; }
        .role-page__hero h1 { font-size: 20px; }
        .role-panel__timestamps { display: none; }
        .role-panel__body { padding: 14px; }
        .role-fields { grid-template-columns: 1fr; gap: 13px; }
        .role-page__actions { padding: 10px; }
        .role-button { min-width: 100px; }
    }
</style>

<script>
(function ($) {
    // 主色跟随后台皮肤（默认/蓝色/浅蓝/绿色...），暗色模式保持自身令牌。
    try {
        var color = window.Dcat && Dcat.color && Dcat.color.get('primary');
        if (color && ! document.body.classList.contains('dark-mode')) {
            document.querySelectorAll('.role-page').forEach(function (el) {
                el.style.setProperty('--role-primary', color);
                el.style.setProperty('--role-primary-dark', Dcat.color.darken(color, 10));
            });
        }
    } catch (e) {
    }

    var $form = $('#role-blade-form');
    if (! $form.length) return;

    $form.off('.roleBladeForm').on('submit.roleBladeForm', function () {
        $(this).find('.role-submit-button').prop('disabled', true);
    });
})(window.jQuery);
</script>

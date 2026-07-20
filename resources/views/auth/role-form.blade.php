<div class="role-page">
    <form id="role-blade-form"
          method="POST"
          action="{{ $editing ? admin_url('auth/roles/'.$role->getKey()) : admin_url('auth/roles') }}"
          autocomplete="off">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <header class="role-page__hero">
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
        </header>

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
                <div class="role-panel__header">
                    <span class="role-panel__number">01</span>
                    <div class="role-panel__heading">
                        <h2>{{ trans('admin.role_basic_information') }}</h2>
                        <p>{{ trans('admin.slug') }} · {{ trans('admin.name') }}</p>
                    </div>
                    @if($editing)
                        <div class="role-panel__timestamps">
                            <span>{{ trans('admin.created_at') }} {{ $role->created_at }}</span>
                            <span>{{ trans('admin.updated_at') }} {{ $role->updated_at }}</span>
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
                    <div class="role-panel__header">
                        <span class="role-panel__number">02</span>
                        <div class="role-panel__heading">
                            <h2>{{ trans('admin.permissions') }}</h2>
                            <p>{{ trans('admin.resource_routes') }} · {{ trans('admin.single_routes') }}</p>
                        </div>
                    </div>
                    <div class="role-panel__body role-panel__body--flush">
                        @include('admin::auth.partials.role-permissions', $permissionEditor)
                    </div>
                </section>

                @if($bindMenu)
                    <section class="role-panel role-panel--menu">
                        <div class="role-panel__header">
                            <span class="role-panel__number">03</span>
                            <div class="role-panel__heading">
                                <h2>{{ trans('admin.menu') }}</h2>
                                <p>{{ trans('admin.role_menu_editor_help') }}</p>
                            </div>
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
    .role-page {
        --role-primary: #6366f1;
        --role-primary-dark: #4f46e5;
        --role-primary-soft: #eef2ff;
        --role-surface: #ffffff;
        --role-surface-soft: #f8fafc;
        --role-surface-hover: #f1f5f9;
        --role-border: #e2e8f0;
        --role-border-strong: #cbd5e1;
        --role-text: #172033;
        --role-muted: #64748b;
        --role-danger: #dc2626;
        --role-danger-soft: #fef2f2;
        color: var(--role-text);
        font-size: 15px;
        line-height: 1.55;
    }

    body.dark-mode .role-page {
        --role-primary: #818cf8;
        --role-primary-dark: #6366f1;
        --role-primary-soft: rgba(99, 102, 241, .16);
        --role-surface: #24243a;
        --role-surface-soft: #1e1e30;
        --role-surface-hover: #2b2b43;
        --role-border: #3b3b55;
        --role-border-strong: #50506d;
        --role-text: #f1f5f9;
        --role-muted: #a8b0c2;
        --role-danger: #f87171;
        --role-danger-soft: rgba(239, 68, 68, .12);
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
        border-radius: 14px;
        background: linear-gradient(135deg, var(--role-surface) 0%, var(--role-primary-soft) 160%);
        box-shadow: 0 8px 28px rgba(15, 23, 42, .06);
    }

    .role-page__identity { display: flex; align-items: center; gap: 16px; min-width: 0; }
    .role-page__hero-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; flex: 0 0 52px;
        border-radius: 14px; color: #fff;
        background: linear-gradient(135deg, var(--role-primary), var(--role-primary-dark));
        box-shadow: 0 8px 18px rgba(79, 70, 229, .24);
        font-size: 23px;
    }
    .role-page__eyebrow { color: var(--role-primary); font-size: 12px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
    .role-page__hero h1 { margin: 2px 0 3px; color: var(--role-text); font-size: 23px; line-height: 1.3; font-weight: 750; }
    .role-page__hero p { margin: 0; color: var(--role-muted); font-size: 14px; }
    .role-page__hero-meta { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
    .role-page__mode-badge, .role-page__id-badge {
        display: inline-flex; align-items: center; gap: 6px;
        min-height: 34px; padding: 6px 12px; border-radius: 9px;
        font-size: 13px; font-weight: 700;
    }
    .role-page__mode-badge { color: var(--role-primary); background: var(--role-primary-soft); }
    .role-page__id-badge { color: var(--role-muted); border: 1px solid var(--role-border); background: var(--role-surface); }

    .role-page__errors {
        display: flex; gap: 12px; padding: 14px 16px; margin-bottom: 16px;
        border: 1px solid rgba(220, 38, 38, .24); border-radius: 12px;
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
        border-radius: 14px;
        background: var(--role-surface);
        box-shadow: 0 5px 20px rgba(15, 23, 42, .045);
    }
    .role-panel__header {
        display: flex; align-items: center; gap: 12px;
        min-height: 68px; padding: 13px 18px;
        border-bottom: 1px solid var(--role-border);
        background: var(--role-surface-soft);
    }
    .role-panel__number {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; flex: 0 0 36px;
        border-radius: 10px; color: var(--role-primary);
        background: var(--role-primary-soft); font-size: 13px; font-weight: 800;
    }
    .role-panel__heading { min-width: 0; }
    .role-panel__heading h2 { margin: 0; color: var(--role-text); font-size: 17px; line-height: 1.35; font-weight: 750; }
    .role-panel__heading p { margin: 2px 0 0; color: var(--role-muted); font-size: 13px; }
    .role-panel__timestamps { margin-left: auto; display: grid; gap: 2px; text-align: right; color: var(--role-muted); font-size: 12px; }
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
        width: 100%; height: 44px; padding: 8px 14px 8px 42px;
        border: 1px solid var(--role-border-strong); border-radius: 9px;
        outline: none; color: var(--role-text); background: var(--role-surface);
        font-size: 15px; transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .role-field input:hover { border-color: var(--role-primary); }
    .role-field input:focus { border-color: var(--role-primary); box-shadow: 0 0 0 3px var(--role-primary-soft); }
    .role-field input[readonly] { cursor: not-allowed; color: var(--role-muted); background: var(--role-surface-soft); }
    .role-field input.is-invalid { border-color: var(--role-danger); box-shadow: 0 0 0 3px var(--role-danger-soft); }
    .role-field__help, .role-field__error { display: flex; align-items: center; gap: 5px; margin-top: 6px; font-size: 12.5px; }
    .role-field__help { color: var(--role-muted); }
    .role-field__error { color: var(--role-danger); }

    .role-page__actions {
        position: sticky; z-index: 20; bottom: 0;
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; padding: 13px 18px; margin-top: 16px;
        border: 1px solid var(--role-border); border-radius: 13px;
        background: var(--role-surface);
        background: color-mix(in srgb, var(--role-surface) 92%, transparent);
        backdrop-filter: blur(12px);
        box-shadow: 0 -6px 24px rgba(15, 23, 42, .08);
    }
    .role-button {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-width: 110px; height: 42px; padding: 0 18px;
        border: 1px solid transparent; border-radius: 9px;
        font-size: 14px; font-weight: 700; text-decoration: none !important;
        cursor: pointer; transition: transform .16s, box-shadow .16s, background .16s;
    }
    .role-button:hover { transform: translateY(-1px); }
    .role-button--secondary { color: var(--role-text); border-color: var(--role-border); background: var(--role-surface); }
    .role-button--secondary:hover { color: var(--role-primary); border-color: var(--role-primary); }
    .role-button--primary { color: #fff; background: linear-gradient(135deg, var(--role-primary), var(--role-primary-dark)); box-shadow: 0 7px 16px rgba(79, 70, 229, .2); }
    .role-button--primary:hover { color: #fff; box-shadow: 0 9px 20px rgba(79, 70, 229, .28); }
    .role-button:disabled { cursor: wait; opacity: .7; transform: none; }

    @media (max-width: 991px) {
        .role-page__authorization-grid { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 767px) {
        .role-page { font-size: 14px; }
        .role-page__hero { align-items: flex-start; padding: 18px; }
        .role-page__hero-meta { display: none; }
        .role-page__hero-icon { width: 44px; height: 44px; flex-basis: 44px; border-radius: 12px; }
        .role-page__hero h1 { font-size: 20px; }
        .role-panel__header { padding: 12px 14px; }
        .role-panel__timestamps { display: none; }
        .role-panel__body { padding: 14px; }
        .role-fields { grid-template-columns: 1fr; gap: 13px; }
        .role-page__actions { padding: 10px; }
        .role-button { min-width: 100px; }
    }
</style>

<script>
(function ($) {
    var $form = $('#role-blade-form');
    if (! $form.length) return;

    $form.off('.roleBladeForm').on('submit.roleBladeForm', function () {
        $(this).find('.role-submit-button').prop('disabled', true);
    });
})(window.jQuery);
</script>

{{-- Standalone Laravel Blade partial; does not use Dcat Form fields. --}}
@php
    $editorId = 'role-permission-editor-'.uniqid();
    $resourceCount = count($resources ?? []);
    $singleCount = count($singles ?? []);
    $customCount = count($customPermissions ?? []);
    $resourcePermissionGroups = [
        'preview' => [
            'label' => 'admin.permission_group_preview',
            'type' => 'view',
            'actions' => ['index', 'show'],
        ],
        'create' => [
            'label' => 'admin.permission_action_create',
            'type' => 'change',
            'actions' => ['create', 'store'],
        ],
        'edit' => [
            'label' => 'admin.permission_action_edit',
            'type' => 'change',
            'actions' => ['edit', 'update'],
        ],
        'delete' => [
            'label' => 'admin.permission_action_destroy',
            'type' => 'change',
            'actions' => ['destroy'],
        ],
        'import' => [
            'label' => 'admin.permission_action_import',
            'type' => 'change',
            'actions' => ['import'],
        ],
        'export' => [
            'label' => 'admin.permission_action_export',
            'type' => 'view',
            'actions' => ['export'],
        ],
    ];
    $safeHttpMethods = ['GET', 'HEAD', 'OPTIONS'];
@endphp

<div id="{{ $editorId }}" class="role-permission-editor">
    <input type="hidden" name="role_authorization_present" value="1">

    <ul class="nav rp-tabs" role="tablist">
        <li>
            <a class="rp-tab active" data-toggle="tab" href="#{{ $editorId }}-resources">
                <i class="feather icon-grid"></i>
                {{ trans('admin.resource_routes') }}
                <span class="rp-tab__count">{{ $resourceCount }}</span>
            </a>
        </li>
        <li>
            <a class="rp-tab" data-toggle="tab" href="#{{ $editorId }}-singles">
                <i class="feather icon-link"></i>
                {{ trans('admin.single_routes') }}
                <span class="rp-tab__count">{{ $singleCount }}</span>
            </a>
        </li>
        <li>
            <a class="rp-tab" data-toggle="tab" href="#{{ $editorId }}-custom">
                <i class="feather icon-key"></i>
                {{ trans('admin.existing_permissions') }}
                <span class="rp-tab__count">{{ $customCount }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content rp-tab-content">
        <div class="tab-pane fade show active" id="{{ $editorId }}-resources">
            <div class="rp-toolbar role-editor-toolbar">
                <div class="rp-search role-editor-search">
                    <i class="feather icon-search"></i>
                    <input type="text" class="rbac-search" data-target=".rbac-resource-card" placeholder="{{ trans('admin.search') }}">
                </div>
                <div class="rp-toolbar__actions">
                    <label class="rp-check-action rp-check-action--category rp-check-action--view" title="{{ trans('admin.view_permissions_help') }}">
                        <input type="checkbox" class="rbac-type-all" data-selector=".rbac-permission-type-view">
                        <span>{{ trans('admin.view_permissions') }}</span>
                    </label>
                    <label class="rp-check-action rp-check-action--category rp-check-action--change" title="{{ trans('admin.data_change_permissions_help') }}">
                        <input type="checkbox" class="rbac-type-all" data-selector=".rbac-permission-type-change">
                        <span>{{ trans('admin.data_change_permissions') }}</span>
                    </label>
                    <label class="rp-check-action">
                        <input type="checkbox" class="rbac-resource-global-all">
                        <span>{{ trans('admin.select_all') }}</span>
                    </label>
                </div>
            </div>

            <div class="rp-scroll">
            @forelse($resources as $resourceIndex => $resource)
                @php
                    $availableActions = array_filter($resource['actions'], fn ($action) => $autoCreate || $action['permission_id']);
                    $allChecked = count($availableActions) > 0 && count(array_filter($availableActions, fn ($action) => $action['checked'])) === count($availableActions);
                @endphp
                <div class="rbac-resource-card rp-resource-card" data-search="{{ strtolower($resource['title'].' '.$resource['uri'].' '.$resource['controller']) }}">
                    <div class="rp-resource-card__header">
                        <div class="rp-resource-card__identity">
                            <strong>{{ $resource['title'] }}</strong>
                            <code>/{{ $resource['uri'] }}</code>
                        </div>
                        <label class="rp-check-action rp-check-action--small">
                            <input type="checkbox" class="rbac-resource-all" {{ $allChecked ? 'checked' : '' }}>
                            <span>{{ trans('admin.select_all') }}</span>
                        </label>
                    </div>
                    <div class="rp-resource-card__body">
                        <div class="rp-action-grid">
                            @foreach($resourcePermissionGroups as $groupKey => $group)
                                @php
                                    $groupActions = array_values(array_filter(
                                        $resource['actions'],
                                        fn ($action) => in_array($action['resource_action'], $group['actions'], true)
                                    ));
                                    $availableGroupActions = array_values(array_filter(
                                        $groupActions,
                                        fn ($action) => $autoCreate || $action['permission_id']
                                    ));
                                    $checkedGroupActions = array_values(array_filter(
                                        $availableGroupActions,
                                        fn ($action) => $action['checked']
                                    ));
                                    $groupDisabled = count($availableGroupActions) !== count($groupActions);
                                    $groupChecked = ! $groupDisabled && count($checkedGroupActions) === count($availableGroupActions);
                                    $groupActionLabels = array_map(
                                        fn ($action) => trans('admin.permission_action_'.$action['resource_action']),
                                        $groupActions
                                    );
                                @endphp

                                @if($groupActions)
                                <div class="rbac-permission-item" data-search="{{ strtolower(trans($group['label']).' '.implode(' ', $groupActionLabels).' '.implode(' ', array_column($groupActions, 'uri'))) }}">
                                    <label class="rp-action-option {{ $groupDisabled ? 'is-disabled' : '' }}" title="{{ $groupDisabled ? trans('admin.role_editor_auto_create_disabled') : implode(', ', array_column($groupActions, 'http_path')) }}">
                                        <input type="checkbox"
                                               class="rbac-resource-group rbac-permission-type-{{ $group['type'] }}"
                                               data-group="{{ $groupKey }}"
                                               data-assignable="{{ $groupDisabled ? '0' : '1' }}"
                                               {{ $groupChecked ? 'checked' : '' }}
                                               {{ $groupDisabled ? 'disabled' : '' }}>
                                        <span>
                                            <strong>{{ trans($group['label']) }}</strong>
                                            <small>{{ implode(' + ', $groupActionLabels) }}</small>
                                        </span>
                                    </label>
                                    <span class="rp-route-values" aria-hidden="true">
                                        @foreach($groupActions as $action)
                                            @php $disabled = ! $autoCreate && ! $action['permission_id']; @endphp
                                            <input type="checkbox"
                                                   name="route_permissions[]"
                                                   value="{{ $action['key'] }}"
                                                   class="rbac-route-permission"
                                                   tabindex="-1"
                                                   {{ $action['checked'] ? 'checked' : '' }}
                                                   {{ $disabled ? 'disabled' : '' }}>
                                        @endforeach
                                    </span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="rp-empty">{{ trans('admin.permission_matrix_empty') }}</div>
            @endforelse
            </div>
        </div>

        <div class="tab-pane fade" id="{{ $editorId }}-singles">
            <div class="rp-toolbar role-editor-toolbar">
                <div class="rp-search role-editor-search">
                    <i class="feather icon-search"></i>
                    <input type="text" class="rbac-search" data-target=".rbac-single-item" placeholder="{{ trans('admin.search') }}">
                </div>
                <div class="rp-toolbar__actions">
                    <label class="rp-check-action rp-check-action--category rp-check-action--view" title="{{ trans('admin.view_permissions_help') }}">
                        <input type="checkbox" class="rbac-type-all" data-selector=".rbac-permission-type-view">
                        <span>{{ trans('admin.view_permissions') }}</span>
                    </label>
                    <label class="rp-check-action rp-check-action--category rp-check-action--change" title="{{ trans('admin.data_change_permissions_help') }}">
                        <input type="checkbox" class="rbac-type-all" data-selector=".rbac-permission-type-change">
                        <span>{{ trans('admin.data_change_permissions') }}</span>
                    </label>
                    <label class="rp-check-action">
                        <input type="checkbox" class="rbac-single-all">
                        <span>{{ trans('admin.select_all') }}</span>
                    </label>
                </div>
            </div>

            <div class="rp-scroll">
            <div class="rp-route-grid">
                @forelse($singles as $route)
                    @php
                        $disabled = ! $autoCreate && ! $route['permission_id'];
                        $methods = array_values(array_unique(array_map('strtoupper', $route['http_methods'])));
                        $permissionType = count($methods) > 0 && count(array_diff($methods, $safeHttpMethods)) === 0
                            ? 'view'
                            : 'change';
                    @endphp
                    <div class="rbac-single-item" data-search="{{ strtolower($route['label'].' '.$route['uri'].' '.$route['controller'].' '.$route['action']) }}">
                        <label class="rp-route-card rp-route-card--simple {{ $disabled ? 'is-disabled' : '' }}" title="{{ $disabled ? trans('admin.role_editor_auto_create_disabled') : $route['http_path'] }}">
                            <input type="checkbox"
                                   name="route_permissions[]"
                                   value="{{ $route['key'] }}"
                                   class="rbac-route-permission rbac-permission-type-{{ $permissionType }}"
                                   {{ $route['checked'] ? 'checked' : '' }}
                                   {{ $disabled ? 'disabled' : '' }}>
                            <span class="rp-route-summary">
                                <strong class="rp-method-badge">{{ implode('|', $route['http_methods']) ?: 'ANY' }}</strong>
                                <span class="rp-route-path">/{{ $route['uri'] }}</span>
                            </span>
                        </label>
                    </div>
                @empty
                    <div class="rp-empty">{{ trans('admin.permission_matrix_empty') }}</div>
                @endforelse
            </div>
            </div>

            @if(! empty($systemRoutes))
                <div class="rp-system-note">
                    <strong>{{ trans('admin.system_routes') }}</strong>
                    <span>{{ trans('admin.system_routes_readonly') }}</span>
                    <div>
                        @foreach($systemRoutes as $route)
                            <code class="mr-2">{{ implode('|', $route['http_methods']) }} /{{ $route['uri'] }}</code>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="{{ $editorId }}-custom">
            <div class="rp-custom-note">{{ trans('admin.existing_permissions_help') }}</div>
            <div class="rp-toolbar role-editor-toolbar">
                <div class="rp-search role-editor-search">
                    <i class="feather icon-search"></i>
                    <input type="text" class="rbac-search" data-target=".rbac-custom-item" placeholder="{{ trans('admin.search') }}">
                </div>
                <label class="rp-check-action">
                    <input type="checkbox" class="rbac-custom-all">
                    <span>{{ trans('admin.select_all') }}</span>
                </label>
            </div>

            <div class="rp-scroll rp-scroll--custom">
            <div class="rp-route-grid">
                @forelse($customPermissions as $permission)
                    <div class="rbac-custom-item" data-search="{{ strtolower($permission['name'].' '.$permission['slug'].' '.implode(' ', $permission['http_path'])) }}">
                        <label class="rp-route-card">
                            <input type="checkbox"
                                   name="custom_permissions[]"
                                   value="{{ $permission['id'] }}"
                                   class="rbac-custom-permission"
                                   {{ $permission['checked'] ? 'checked' : '' }}>
                            <span class="text-break">
                                <strong>{{ $permission['name'] }}</strong>
                                <code>{{ $permission['slug'] }}</code>
                                @if($permission['parent_id'])<small>#{{ $permission['parent_id'] }}</small>@endif
                                <small>
                                    {{ $permission['http_method'] ? implode('|', $permission['http_method']) : 'ANY' }}
                                    · {{ implode(', ', $permission['http_path']) ?: '-' }}
                                </small>
                            </span>
                        </label>
                    </div>
                @empty
                    <div class="rp-empty">{{ trans('admin.no_custom_permissions') }}</div>
                @endforelse
            </div>
            </div>
        </div>
    </div>
</div>

<style>
    #{{ $editorId }} { color: var(--role-text); font-size: 16px; }
    #{{ $editorId }} label { margin: 0; cursor: pointer; font-weight: 400; }
    #{{ $editorId }} input[type="checkbox"] { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; flex: 0 0 18px; margin: 0; border: 1.5px solid var(--role-border-strong); border-radius: 5px; outline: none; background-color: var(--role-surface); background-position: center; background-repeat: no-repeat; background-size: 12px 12px; cursor: pointer; transition: border-color .15s, background-color .15s, box-shadow .15s, transform .15s; }
    #{{ $editorId }} input[type="checkbox"]:hover { border-color: var(--rp-accent, var(--role-primary)); transform: translateY(-1px); }
    #{{ $editorId }} input[type="checkbox"]:focus-visible { border-color: var(--rp-accent, var(--role-primary)); box-shadow: 0 0 0 3px var(--role-primary-soft); }
    #{{ $editorId }} input[type="checkbox"]:checked { border-color: var(--rp-accent, var(--role-primary)); background-color: var(--rp-accent, var(--role-primary)); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.2 6.2 4.8 8.7 9.9 3.3'/%3E%3C/svg%3E"); }
    #{{ $editorId }} input[type="checkbox"]:indeterminate { border-color: var(--rp-accent, var(--role-primary)); background-color: var(--rp-accent, var(--role-primary)); background-image: linear-gradient(#fff, #fff); background-size: 9px 2px; }
    #{{ $editorId }} input[type="checkbox"]:disabled { cursor: not-allowed; opacity: .5; transform: none; }
    #{{ $editorId }} .rp-tabs { display: flex; gap: 3px; padding: 7px 12px 0; margin: 0; border-bottom: 1px solid var(--role-border); background: var(--role-surface); list-style: none; }
    #{{ $editorId }} .rp-tabs li { margin: 0; }
    #{{ $editorId }} .rp-tab { position: relative; display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 7px 13px; color: var(--role-muted); border-radius: 9px 9px 0 0; font-size: 15px; font-weight: 700; text-decoration: none; }
    #{{ $editorId }} .rp-tab:hover { color: var(--role-primary); background: var(--role-surface-soft); }
    #{{ $editorId }} .rp-tab.active { color: var(--role-primary); background: var(--role-primary-soft); }
    #{{ $editorId }} .rp-tab.active::after { content: ''; position: absolute; left: 12px; right: 12px; bottom: -1px; height: 2px; background: var(--role-primary); }
    #{{ $editorId }} .rp-tab__count { min-width: 22px; padding: 1px 6px; border-radius: 10px; text-align: center; color: var(--role-muted); background: var(--role-surface-hover); font-size: 12px; }
    #{{ $editorId }} .rp-tab.active .rp-tab__count { color: var(--role-primary); background: var(--role-surface); }
    #{{ $editorId }} .rp-tab-content { padding: 10px 12px 12px; background: var(--role-surface); }
    #{{ $editorId }} .rp-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
    #{{ $editorId }} .rp-toolbar__actions { display: flex; align-items: center; justify-content: flex-end; gap: 9px; }
    #{{ $editorId }} .rp-search { position: relative; width: 320px; max-width: 100%; }
    #{{ $editorId }} .rp-search > i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--role-muted); font-size: 15px; }
    #{{ $editorId }} .rp-search input { width: 100%; height: 40px; padding: 7px 12px 7px 36px; border: 1px solid var(--role-border); border-radius: 9px; outline: none; color: var(--role-text); background: var(--role-surface-soft); font-size: 15px; }
    #{{ $editorId }} .rp-search input:focus { border-color: var(--role-primary); background: var(--role-surface); box-shadow: 0 0 0 3px var(--role-primary-soft); }
    #{{ $editorId }} .rp-check-action { display: inline-flex; align-items: center; gap: 8px; color: var(--role-primary); font-size: 15px; font-weight: 700; white-space: nowrap; }
    #{{ $editorId }} .rp-check-action--small { font-size: 14px; }
    #{{ $editorId }} .rp-check-action--category { min-height: 36px; padding: 5px 9px; border: 1px solid var(--role-border); border-radius: 8px; background: var(--role-surface-soft); font-size: 13.5px; transition: border-color .15s, background .15s; }
    #{{ $editorId }} .rp-check-action--category:hover { border-color: var(--rp-accent); background: var(--role-surface); }
    #{{ $editorId }} .rp-check-action--view { --rp-accent: #10b981; color: #0f8a63; }
    #{{ $editorId }} .rp-check-action--change { --rp-accent: #f59e0b; color: #b45309; }
    body.dark-mode #{{ $editorId }} .rp-check-action--view { color: #6ee7b7; }
    body.dark-mode #{{ $editorId }} .rp-check-action--change { color: #fcd34d; }
    #{{ $editorId }} .rp-scroll { max-height: 560px; overflow: auto; padding-right: 4px; scrollbar-width: thin; scrollbar-color: var(--role-border-strong) transparent; }
    #{{ $editorId }} .rp-resource-card { margin-bottom: 7px; overflow: hidden; border: 1px solid var(--role-border); border-radius: 10px; background: var(--role-surface); transition: border-color .15s, box-shadow .15s; }
    #{{ $editorId }} .rp-resource-card:hover { border-color: var(--role-border-strong); box-shadow: 0 4px 12px rgba(15, 23, 42, .045); }
    #{{ $editorId }} .rp-resource-card__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 44px; padding: 7px 11px; border-bottom: 1px solid var(--role-border); background: var(--role-surface-soft); }
    #{{ $editorId }} .rp-resource-card__identity { display: flex; align-items: center; gap: 8px; min-width: 0; }
    #{{ $editorId }} .rp-resource-card__identity strong { color: var(--role-text); font-size: 15.5px; line-height: 1.25; font-weight: 750; }
    #{{ $editorId }} .rp-resource-card__identity code { max-width: 360px; overflow: hidden; padding: 2px 6px; color: var(--role-primary); background: var(--role-primary-soft); text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
    #{{ $editorId }} .rp-resource-card__body { padding: 6px 8px; }
    #{{ $editorId }} .rp-action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 3px 6px; }
    #{{ $editorId }} .rp-route-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 7px; }
    #{{ $editorId }} .rbac-permission-item, #{{ $editorId }} .rbac-single-item, #{{ $editorId }} .rbac-custom-item { min-width: 0; padding: 0; }
    #{{ $editorId }} .rp-route-values { display: none !important; }
    #{{ $editorId }} .rp-action-option { display: flex; align-items: center; gap: 9px; min-height: 43px; padding: 5px 8px; border-radius: 8px; transition: background .15s; }
    #{{ $editorId }} .rp-action-option:hover { background: var(--role-surface-hover); }
    #{{ $editorId }} .rp-action-option > span { min-width: 0; }
    #{{ $editorId }} .rp-action-option strong { display: block; color: var(--role-text); font-size: 12px; line-height: 1.25; font-weight: 650; }
    #{{ $editorId }} .rp-action-option small { display: block; margin-top: 2px; color: var(--role-muted); font-size: 12px; line-height: 1.25; }
    #{{ $editorId }} .rp-route-card { display: flex; align-items: flex-start; gap: 9px; width: 100%; min-height: 70px; padding: 9px 10px; border: 1px solid var(--role-border); border-radius: 9px; background: var(--role-surface); transition: border-color .15s, background .15s, transform .15s; }
    #{{ $editorId }} .rp-route-card:hover { border-color: var(--role-primary); background: var(--role-primary-soft); transform: translateY(-1px); }
    #{{ $editorId }} .rp-route-card > span { display: block; min-width: 0; }
    #{{ $editorId }} .rp-route-card strong { display: inline; color: var(--role-text); font-size: 16px; line-height: 1.3; font-weight: 700; }
    #{{ $editorId }} .rp-route-card code { margin-left: 5px; color: var(--role-primary); background: var(--role-primary-soft); font-size: 13px; }
    #{{ $editorId }} .rp-route-card small { display: block; margin-top: 3px; overflow: hidden; color: var(--role-muted); text-overflow: ellipsis; white-space: nowrap; font-size: 13px; line-height: 1.25; }
    #{{ $editorId }} .rp-route-card--simple { align-items: flex-start; min-height: 72px; }
    #{{ $editorId }} .rp-route-card--simple > input { margin-top: 5px; }
    #{{ $editorId }} .rp-route-summary { display: grid !important; gap: 4px; min-width: 0; }
    #{{ $editorId }} .rp-method-badge { display: inline-flex !important; align-items: center; justify-content: center; width: max-content; min-width: 50px; min-height: 25px; padding: 2px 8px; border-radius: 7px; color: var(--role-primary) !important; background: var(--role-primary-soft); font-size: 13px !important; line-height: 1.2 !important; font-weight: 800 !important; letter-spacing: .02em; }
    #{{ $editorId }} .rp-route-path { min-width: 0; overflow: hidden; color: var(--role-text); text-overflow: ellipsis; white-space: nowrap; font-size: 15px; line-height: 1.3; font-weight: 650; }
    #{{ $editorId }} .is-disabled { cursor: not-allowed; opacity: .55; }
    #{{ $editorId }} .is-disabled:hover { border-color: var(--role-border); background: var(--role-surface); transform: none; }
    #{{ $editorId }} .rp-system-note, #{{ $editorId }} .rp-custom-note { padding: 9px 11px; margin-bottom: 8px; border: 1px solid var(--role-border); border-radius: 9px; color: var(--role-muted); background: var(--role-surface-soft); font-size: 14px; }
    #{{ $editorId }} .rp-system-note { margin-top: 10px; margin-bottom: 0; }
    #{{ $editorId }} .rp-system-note strong { color: var(--role-text); margin-right: 5px; }
    #{{ $editorId }} .rp-system-note > div { margin-top: 6px; }
    #{{ $editorId }} .rp-empty { padding: 28px 10px; text-align: center; color: var(--role-muted); font-size: 14px; }

    @media (max-width: 767px) {
        #{{ $editorId }} .rp-tabs { padding-left: 8px; padding-right: 8px; overflow-x: auto; }
        #{{ $editorId }} .rp-tab { padding: 8px 10px; white-space: nowrap; }
        #{{ $editorId }} .rp-tab-content { padding: 10px; }
        #{{ $editorId }} .rp-toolbar { align-items: stretch; flex-direction: column; }
        #{{ $editorId }} .rp-toolbar__actions { justify-content: flex-start; flex-wrap: wrap; }
        #{{ $editorId }} .rp-search { width: 100%; }
        #{{ $editorId }} .rp-check-action { align-self: auto; }
        #{{ $editorId }} .rp-resource-card__identity code { max-width: 170px; }
        #{{ $editorId }} .rp-scroll { max-height: 500px; }
        #{{ $editorId }} .rp-action-grid, #{{ $editorId }} .rp-route-grid { grid-template-columns: 1fr; }
    }

    @media (min-width: 768px) and (max-width: 1199px) {
        #{{ $editorId }} .rp-action-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        #{{ $editorId }} .rp-route-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

<script>
(function ($) {
    var $root = $('#{{ $editorId }}');
    if (! $root.length) return;

    var namespace = '.rolePermissionEditor';

    function available($scope, selector) {
        return $scope.find(selector).filter(':not(:disabled):visible');
    }

    function refreshAll($scope, itemSelector, allSelector) {
        var $items = available($scope, itemSelector),
            checked = $items.filter(':checked').length,
            partial = $items.filter(function () { return this.indeterminate; }).length,
            $all = $scope.find(allSelector);

        $all.prop('checked', $items.length > 0 && checked === $items.length);
        $all.prop('indeterminate', partial > 0 || (checked > 0 && checked < $items.length));
    }

    function refreshPermissionTypes($pane) {
        $pane.find('.rbac-type-all').each(function () {
            var $selector = $(this),
                itemSelector = String($selector.data('selector') || ''),
                $items = itemSelector ? available($pane, itemSelector) : $(),
                checked = $items.filter(':checked').length,
                partial = $items.filter(function () { return this.indeterminate; }).length;

            $selector
                .prop('disabled', $items.length === 0)
                .prop('checked', $items.length > 0 && checked === $items.length)
                .prop('indeterminate', partial > 0 || (checked > 0 && checked < $items.length));
        });
    }

    function resourceGroupRoutes($control, assignableOnly) {
        var $routes = $control.closest('.rbac-permission-item').find('.rbac-route-permission');

        return assignableOnly ? $routes.filter(':not(:disabled)') : $routes;
    }

    function syncResourceGroup($control) {
        resourceGroupRoutes($control, true).prop('checked', $control.prop('checked'));
        $control.prop('indeterminate', false);
    }

    function refreshResourceGroup($control) {
        var $routes = resourceGroupRoutes($control, false),
            checked = $routes.filter(':checked').length,
            assignable = String($control.data('assignable')) === '1';

        $control
            .prop('disabled', !assignable || $routes.length === 0)
            .prop('checked', assignable && $routes.length > 0 && checked === $routes.length)
            .prop('indeterminate', checked > 0 && checked < $routes.length);
    }

    function refreshPane($pane) {
        $pane.find('.rbac-resource-group').each(function () {
            refreshResourceGroup($(this));
        });
        $pane.find('.rbac-resource-card:visible').each(function () {
            refreshAll($(this), '.rbac-resource-group', '.rbac-resource-all');
        });
        refreshAll($pane, '.rbac-resource-card:visible .rbac-resource-group', '.rbac-resource-global-all');
        refreshAll($pane, '.rbac-single-item:visible .rbac-route-permission', '.rbac-single-all');
        refreshAll($pane, '.rbac-custom-item:visible .rbac-custom-permission', '.rbac-custom-all');
        refreshPermissionTypes($pane);
    }

    $root.off(namespace)
        .on('change' + namespace, '.rbac-resource-all', function () {
            var $card = $(this).closest('.rbac-resource-card'),
                $pane = $(this).closest('.tab-pane');
            available($card, '.rbac-resource-group').prop('checked', this.checked).each(function () {
                syncResourceGroup($(this));
            });
            refreshPane($pane);
        })
        .on('change' + namespace, '.rbac-resource-global-all', function () {
            var $pane = $(this).closest('.tab-pane');
            available($pane, '.rbac-resource-card:visible .rbac-resource-group').prop('checked', this.checked).each(function () {
                syncResourceGroup($(this));
            });
            refreshPane($pane);
        })
        .on('change' + namespace, '.rbac-type-all', function () {
            var $pane = $(this).closest('.tab-pane'),
                selector = String($(this).data('selector') || '');

            if (selector) {
                available($pane, selector).prop('checked', this.checked).each(function () {
                    var $item = $(this);
                    if ($item.hasClass('rbac-resource-group')) syncResourceGroup($item);
                });
            }
            refreshPane($pane);
        })
        .on('change' + namespace, '.rbac-resource-group', function () {
            syncResourceGroup($(this));
            refreshPane($(this).closest('.tab-pane'));
        })
        .on('change' + namespace, '.rbac-single-all', function () {
            var $pane = $(this).closest('.tab-pane');
            available($pane, '.rbac-single-item:visible .rbac-route-permission').prop('checked', this.checked);
            refreshPane($pane);
        })
        .on('change' + namespace, '.rbac-single-item .rbac-route-permission', function () {
            refreshPane($(this).closest('.tab-pane'));
        })
        .on('change' + namespace, '.rbac-custom-all', function () {
            var $pane = $(this).closest('.tab-pane');
            available($pane, '.rbac-custom-item:visible .rbac-custom-permission').prop('checked', this.checked);
            refreshPane($pane);
        })
        .on('change' + namespace, '.rbac-custom-permission', function () {
            refreshPane($(this).closest('.tab-pane'));
        })
        .on('input' + namespace, '.rbac-search', function () {
            var keyword = $.trim($(this).val()).toLowerCase(),
                target = $(this).data('target'),
                $pane = $(this).closest('.tab-pane');

            $pane.find(target).each(function () {
                var matched = !keyword || String($(this).data('search') || '').indexOf(keyword) !== -1;
                $(this).toggle(matched);
            });

            refreshPane($pane);
        })
        .on('shown.bs.tab' + namespace, 'a[data-toggle="tab"]', function () {
            refreshPane($($(this).attr('href')));
        });

    $root.find('.tab-pane').each(function () {
        refreshPane($(this));
    });
})(window.jQuery);
</script>

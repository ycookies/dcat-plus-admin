@if($user)
@php
    $roleSwitchEnabled = (bool) config('admin.permission.active_role.enable', false);
    $activeRole = null;
    $availableRoles = collect();

    if ($roleSwitchEnabled) {
        try {
            $resolver = app(\Dcat\Admin\Support\Authorization\ActiveRole::class);
            $availableRoles = $resolver->roles($user);
            $activeRole = $resolver->current($user);
        } catch (\Throwable) {}
    }
@endphp
<li class="dropdown dropdown-user nav-item">
    <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
        <div class="user-nav d-sm-flex d-none">
            <span class="user-name text-bold-600">{{ $user->name }}</span>
            <span class="user-status"><i class="fa fa-circle text-success"></i> {{ trans('admin.online') }}</span>
        </div>
        <span>
            <img class="round" src="{{ $user->getAvatar() }}" alt="avatar" height="40" width="40" />
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-right">

        @if($roleSwitchEnabled)
            <div class="dcat-user-role-panel">
                @if($availableRoles->count() > 1)
                    @php
                        $roleListId = 'dcat-user-role-list-'.$user->getAuthIdentifier();
                    @endphp
                @endif
                <div class="dcat-user-role-panel__current">
                    <span class="dcat-user-role-panel__icon"><i class="feather icon-shield"></i></span>
                    <span class="dcat-user-role-panel__current-content">
                        <small>{{ trans('admin.current_role') }}</small>
                        <strong>{{ $activeRole->name ?? trans('admin.no_active_role') }}</strong>
                    </span>
                    @if($availableRoles->count() > 1)
                        <button type="button" class="dcat-user-role-panel__toggle"
                                data-target="{{ $roleListId }}" aria-expanded="false">
                            <span>{{ trans('admin.switch_role') }}</span><i class="feather icon-chevron-down"></i>
                        </button>
                    @endif
                </div>

                @if($availableRoles->count() > 1)
                    <div id="{{ $roleListId }}" class="dcat-user-role-panel__list" hidden>
                        <div class="dcat-user-role-panel__switch-label">{{ trans('admin.switch_role') }}</div>
                        <div class="dcat-user-role-panel__items">
                            @foreach($availableRoles as $role)
                                @php
                                    $isActiveRole = $activeRole && $activeRole->getKey() == $role->getKey();
                                @endphp
                                <button type="button" class="dcat-active-role-switch {{ $isActiveRole ? 'active' : '' }}"
                                        data-role-id="{{ $role->getKey() }}">
                                    <span class="dcat-active-role-option__icon"><i class="feather icon-shield"></i></span>
                                    <span class="dcat-active-role-option__content">
                                        <strong>{{ $role->name }}</strong>
                                        <small>{{ $isActiveRole ? trans('admin.current_using') : trans('admin.switch_to_role') }}</small>
                                    </span>
                                    @if($isActiveRole)
                                        <span class="dcat-active-role-option__check"><i class="feather icon-check"></i></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="dropdown-divider"></div>
        @endif

        <a href="{{ admin_url('auth/setting') }}" class="dropdown-item">
            <i class="feather icon-user"></i> {{ trans('admin.setting') }}
        </a>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item" href="{{ admin_url('auth/logout') }}">
            <i class="feather icon-power"></i> {{ trans('admin.logout') }}
        </a>
    </div>
</li>
@endif

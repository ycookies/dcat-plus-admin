{{-- Standalone Laravel Blade partial; does not use Dcat Form fields. --}}
@php
    $treeId = 'role-menu-editor-'.uniqid();
    $nodes = $nodes ?? [];
    $selectedIds = array_values(array_map(fn ($node) => $node['id'], array_filter($nodes, fn ($node) => ! empty($node['state']['selected']))));
@endphp

<div class="role-menu-editor" id="{{ $treeId }}">
    <div class="rm-toolbar role-menu-toolbar">
        <div class="rm-search-box role-menu-search">
            <i class="feather icon-search"></i>
            <input type="text" class="rm-search" placeholder="{{ trans('admin.search') }}">
        </div>
        <div class="rm-toolbar__actions">
            <label class="rm-toggle">
                <input type="checkbox" class="rm-check-all">
                <span>{{ trans('admin.select_all') }}</span>
            </label>
            <label class="rm-toggle">
                <input type="checkbox" class="rm-expand" checked>
                <span>{{ trans('admin.expand') }}</span>
            </label>
            <span class="rm-selected">
                {{ trans('admin.selected') }}<strong class="rm-selected-count">{{ count($selectedIds) }}</strong>
            </span>
        </div>
    </div>

    <input type="hidden" name="role_menus" class="rm-input" value="{{ implode(',', $selectedIds) }}">

    <div class="jstree-wrapper">
        <div class="da-tree"></div>
    </div>
</div>

<style>
    #{{ $treeId }} { color: var(--role-text); font-size: 15px; }
    #{{ $treeId }} *, #{{ $treeId }} *::before, #{{ $treeId }} *::after { box-sizing: border-box; }
    #{{ $treeId }} .rm-toolbar { display: flex; align-items: stretch; flex-direction: column; gap: 9px; padding: 10px 12px; border-bottom: 1px solid var(--role-border); background: var(--role-surface); }
    #{{ $treeId }} .role-menu-search { width: 100%; max-width: 100%; }
    #{{ $treeId }} .rm-search-box { position: relative; }
    #{{ $treeId }} .rm-search-box > i { position: absolute; z-index: 1; left: 12px; top: 50%; transform: translateY(-50%); color: var(--role-muted); font-size: 15px; pointer-events: none; }
    #{{ $treeId }} .rm-search { width: 100%; height: 40px; padding: 7px 12px 7px 36px; border: 1px solid var(--role-border); border-radius: 9px; outline: none; color: var(--role-text); background: var(--role-surface-soft); font-size: 15px; }
    #{{ $treeId }} .rm-search:focus { border-color: var(--role-primary); background: var(--role-surface); box-shadow: 0 0 0 3px var(--role-primary-soft); }
    #{{ $treeId }} .rm-toolbar__actions { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; }
    #{{ $treeId }} .rm-toggle { display: inline-flex; align-items: center; gap: 7px; min-height: 36px; padding: 6px 10px; margin: 0; border: 1px solid var(--role-border); border-radius: 8px; color: var(--role-primary); background: var(--role-surface); cursor: pointer; font-size: 14px; font-weight: 700; white-space: nowrap; }
    #{{ $treeId }} .rm-toggle:hover { border-color: var(--role-primary); background: var(--role-primary-soft); }
    #{{ $treeId }} .rm-toolbar input[type="checkbox"] { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; flex: 0 0 18px; margin: 0; border: 1.5px solid var(--role-border-strong); border-radius: 5px; outline: none; background-color: var(--role-surface); background-position: center; background-repeat: no-repeat; background-size: 12px 12px; cursor: pointer; transition: border-color .15s, background-color .15s, box-shadow .15s, transform .15s; }
    #{{ $treeId }} .rm-toolbar input[type="checkbox"]:hover { border-color: var(--role-primary); transform: translateY(-1px); }
    #{{ $treeId }} .rm-toolbar input[type="checkbox"]:focus-visible { border-color: var(--role-primary); box-shadow: 0 0 0 3px var(--role-primary-soft); }
    #{{ $treeId }} .rm-toolbar input[type="checkbox"]:checked { border-color: var(--role-primary); background-color: var(--role-primary); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.2 6.2 4.8 8.7 9.9 3.3'/%3E%3C/svg%3E"); }
    #{{ $treeId }} .rm-toolbar input[type="checkbox"]:indeterminate { border-color: var(--role-primary); background-color: var(--role-primary); background-image: linear-gradient(#fff, #fff); background-size: 9px 2px; }
    #{{ $treeId }} .rm-selected { display: inline-flex; align-items: center; gap: 7px; min-height: 36px; padding: 6px 10px; border-radius: 8px; color: var(--role-muted); background: var(--role-surface-soft); font-size: 14px; white-space: nowrap; }
    #{{ $treeId }} .rm-selected-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 12px; color: #fff; background: var(--role-primary); font-size: 12px; }
    #{{ $treeId }} .jstree-wrapper { max-height: 480px; min-height: 130px; padding: 8px 12px; overflow: auto; background: var(--role-surface); scrollbar-width: thin; scrollbar-color: var(--role-border-strong) transparent; }
    #{{ $treeId }} .jstree-anchor { height: 32px !important; line-height: 32px !important; color: var(--role-text) !important; font-size: 15px !important; }
    #{{ $treeId }} .jstree-wholerow { height: 32px !important; border-radius: 6px; }
    #{{ $treeId }} .jstree-wholerow-hovered { background: var(--role-surface-hover) !important; }
    #{{ $treeId }} .jstree-wholerow-clicked { background: var(--role-primary-soft) !important; }
    #{{ $treeId }} .jstree-checkbox { position: relative; width: 18px !important; height: 18px !important; margin: 7px 7px 0 0 !important; border: 1.5px solid var(--role-border-strong); border-radius: 5px; background: var(--role-surface) none center/12px 12px no-repeat !important; transition: border-color .15s, background-color .15s, box-shadow .15s; }
    #{{ $treeId }} .jstree-anchor:hover > .jstree-checkbox { border-color: var(--role-primary); }
    #{{ $treeId }} .jstree-clicked > .jstree-checkbox { border-color: var(--role-primary); background-color: var(--role-primary) !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.2 6.2 4.8 8.7 9.9 3.3'/%3E%3C/svg%3E") !important; }
    #{{ $treeId }} .jstree-checkbox.jstree-undetermined { border-color: var(--role-primary); background-color: var(--role-primary) !important; background-image: linear-gradient(#fff, #fff) !important; background-size: 9px 2px !important; }
    #{{ $treeId }} .jstree-container-ul { margin-top: 2px; }

    @media (max-width: 767px) {
        #{{ $treeId }} .rm-selected { margin-left: auto; }
        #{{ $treeId }} .jstree-wrapper { padding-left: 6px; padding-right: 6px; }
    }
</style>

<script>
(function initializeRoleMenu(attempt) {
    var $ = window.jQuery,
        root = document.getElementById('{{ $treeId }}');

    // Dcat appends required assets near the end of the response, while this
    // Blade partial is rendered in the page body. Wait until jsTree is ready.
    if (! root) return;
    if (! $ || ! $.fn || ! $.fn.jstree) {
        if (attempt < 100) {
            window.setTimeout(function () {
                initializeRoleMenu(attempt + 1);
            }, 50);
            return;
        }

        console.error('Role menu editor failed to load jsTree.');
        return;
    }

    var $root = $(root);
    if ($root.data('role-menu-initialized')) return;
    $root.data('role-menu-initialized', true);

    var $tree = $root.find('.da-tree'),
        $input = $root.find('.rm-input'),
        $count = $root.find('.rm-selected-count'),
        namespace = '.roleMenuEditor',
        searchTimer,
        opts = {
            core: {
                data: @json($nodes),
                themes: { name: 'proton', responsive: true },
                check_callback: true,
                // Flat menu data is small and is replaced frequently by PJAX.
                // Parsing it synchronously avoids stale Blob workers continuing
                // after the role page has already been replaced.
                worker: false
            },
            plugins: ['checkbox', 'types', 'search'],
            checkbox: {
                three_state: {{ $cascade ? 'true' : 'false' }},
                cascade: @json($cascade ? 'up+down+undetermined' : ''),
                keep_selected_style: false
            },
            types: { default: { icon: false } },
            search: { show_only_matches: true, show_only_matches_children: true, fuzzy: false }
        };

    function syncSelected() {
        var instance = $tree.jstree(true),
            selected = instance ? instance.get_selected() : [];

        $input.val(selected.join(','));
        $count.text(selected.length);
        $root.find('.rm-check-all')
            .prop('checked', selected.length > 0 && selected.length === {{ count($nodes) }})
            .prop('indeterminate', selected.length > 0 && selected.length < {{ count($nodes) }});
    }

    if ($tree.hasClass('jstree')) {
        $tree.jstree('destroy');
    }

    $tree.off(namespace)
        .on('changed.jstree' + namespace, syncSelected)
        .on('loaded.jstree' + namespace, function () {
            $tree.jstree('open_all');
            syncSelected();
        })
        .jstree(opts);

    $root.off(namespace)
        .on('input' + namespace, '.rm-search', function () {
            clearTimeout(searchTimer);
            var value = this.value;
            searchTimer = setTimeout(function () {
                $tree.jstree(true).search(value);
            }, 200);
        })
        .on('change' + namespace, '.rm-check-all', function () {
            $tree.jstree(this.checked ? 'check_all' : 'uncheck_all');
            syncSelected();
        })
        .on('change' + namespace, '.rm-expand', function () {
            $tree.jstree(this.checked ? 'open_all' : 'close_all');
        });
})(0);
</script>

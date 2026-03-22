<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">
    <label class="{{$viewClass['label']}} control-label">{{$label}}</label>
    <div class="{{$viewClass['field']}}">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $label }}</h3>
                <div class="card-tools">
                    {{--<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addItemModal-{{ $id }}">
                        <i class="fa fa-plus"></i> 新增
                    </button>--}}
                    {!! $createForm !!}
                </div>
            </div>
            <div class="card-body p-0">
                <div class="draggable-list dd" id="{{ $id }}">
                    <ol class="dd-list">
                        @foreach($value as $key => $item)
                        <li class="dd-item" data-id="{{ $item['id'] }}" 
                            data-icon="{{ $item['icon'] }}"
                            data-title="{{ $item['title'] }}"
                            data-path="{{ $item['path'] }}">
                            <div class="dd-handle d-flex justify-content-between">
                                <div>
                                    <i class="{{ $item['icon'] }}"></i>
                                    {{ $item['title'] }}
                                </div>
                                <span class="pull-right dd-nodrag">
                                    <a href="javascript:void(0);" class="tree-quick-edit">
                                        <i class="feather icon-edit"></i>&nbsp;
                                    </a>
                                    <a href="javascript:void(0);" class="tree-quick-edit">
                                        <i class="feather icon-trash"></i>&nbsp;
                                    </a>
                                </span>
                            </div>
                        </li>
                        @endforeach
                    </ol>
                    
                    <input type="hidden" name="{{ $name }}" value="{{ json_encode($value) }}" />
                </div>
            </div>
        </div>

        @include('admin::form.error')
        @include('admin::form.help-block')
    </div>
</div>

<div class="modal fade" id="addItemModal-{{ $id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">新增项目</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addItemForm-{{ $id }}">
                    <div class="form-group">
                        <label for="itemTitle-{{ $id }}">标题</label>
                        <input type="text" class="form-control" id="itemTitle-{{ $id }}" required>
                    </div>
                    <div class="form-group">
                        <label for="itemIcon-{{ $id }}">图标</label>
                        <input type="text" class="form-control" id="itemIcon-{{ $id }}" placeholder="fa fa-book">
                    </div>
                    <div class="form-group">
                        <label for="itemPath-{{ $id }}">路径</label>
                        <input type="text" class="form-control" id="itemPath-{{ $id }}">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveNewItem('{{ $id }}')">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
Dcat.ready(function () {
    var id = '{{ $id }}';
    var sortable = Sortable.create(document.getElementById(id).querySelector('.dd-list'), {
        group: id,
        animation: 150,
        handle: '.dd-handle',
        ghostClass: 'dd-ghost',
        chosenClass: 'dd-chosen',
        dragClass: 'dd-drag',
        
        onEnd: function (evt) {
            // 获取所有项的数据并保持原有格式
            var items = [];
            document.querySelectorAll('#' + id + ' .dd-item').forEach(function(el) {
                items.push({
                    id: el.dataset.id,
                    icon: el.dataset.icon,
                    title: el.dataset.title,
                    path: el.dataset.path
                });
            });
            
            // 更新隐藏输入框的值
            $('input[name="{{ $name }}"]').val(JSON.stringify(items));
            
            // 触发 change 事件
            $('input[name="{{ $name }}"]').trigger('change');
            
            // 输出调试信息
            console.log('Updated items:', items);
        },
        
        // 删除功能
        filter: '.icon-trash',
        onFilter: function (evt) {
            var item = evt.item,
                ctrl = evt.target;
            
            if (Sortable.utils.is(ctrl, ".icon-trash")) {
                $(item).fadeOut(function() {
                    $(this).remove();
                    
                    // 获取更新后的数据
                    var items = [];
                    document.querySelectorAll('#' + id + ' .dd-item').forEach(function(el) {
                        items.push({
                            id: el.dataset.id,
                            icon: el.dataset.icon,
                            title: el.dataset.title,
                            path: el.dataset.path
                        });
                    });
                    
                    // 更新隐藏输入框的值
                    $('input[name="{{ $name }}"]').val(JSON.stringify(items));
                    
                    // 触发 change 事件
                    $('input[name="{{ $name }}"]').trigger('change');
                });
            }
        }
    });
    
    // 添加新项目的函数
    window.saveNewItem = function(id) {
        var title = $('#itemTitle-' + id).val();
        var icon = $('#itemIcon-' + id).val() || 'fa fa-bars';
        var path = $('#itemPath-' + id).val() || '';
        
        if (!title) {
            Dcat.error('请输入标题');
            return;
        }
        
        // 创建新项目
        var newItem = {
            id: Date.now().toString(), // 生成唯一ID
            title: title,
            icon: icon,
            path: path
        };
        
        // 添加到列表
        var itemHtml = `
            <li class="dd-item" 
                data-id="${newItem.id}" 
                data-icon="${newItem.icon}"
                data-title="${newItem.title}"
                data-path="${newItem.path}">
                <div class="dd-handle d-flex justify-content-between">
                    <div>
                        <i class="${newItem.icon}"></i>
                        ${newItem.title}
                    </div>
                    <span class="pull-right dd-nodrag">
                        <a href="javascript:void(0);" class="tree-quick-edit">
                            <i class="feather icon-edit"></i>&nbsp;
                        </a>
                        <a href="javascript:void(0);" class="tree-quick-edit">
                            <i class="feather icon-trash"></i>&nbsp;
                        </a>
                    </span>
                </div>
            </li>
        `;
        
        $('#' + id + ' .dd-list').append(itemHtml);
        
        // 更新隐藏输入框的值
        var items = [];
        document.querySelectorAll('#' + id + ' .dd-item').forEach(function(el) {
            items.push({
                id: el.dataset.id,
                icon: el.dataset.icon,
                title: el.dataset.title,
                path: el.dataset.path
            });
        });
        
        $('input[name="{{ $name }}"]').val(JSON.stringify(items));
        $('input[name="{{ $name }}"]').trigger('change');
        
        // 关闭模态框并重置表单
        $('#addItemModal-' + id).modal('hide');
        $('#addItemForm-' + id)[0].reset();
        
        // 提示成功
        Dcat.success('添加成功');
    };
});
</script>

<style>
.draggable-list .dd-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.draggable-list .dd-item {
    margin: 5px 0;
    padding: 0;
}

.draggable-list .dd-handle {
    height: 40px;
    padding: 8px 15px;
    margin: 5px 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: move;
    display: flex;
    align-items: center;
}

.draggable-list .dd-handle:hover {
    background: #f5f5f5;
}

.draggable-list .dd-ghost {
    opacity: 0.4;
    background: #c8ebfb;
    border: 2px dashed #4a9eff;
}

.draggable-list .dd-chosen {
    background: #e0f3ff;
}

.draggable-list .dd-drag {
    opacity: 0.8;
}

.draggable-list .dd-nodrag {
    cursor: pointer;
}

.draggable-list .dd-nodrag a {
    color: #999;
}

.draggable-list .dd-nodrag a:hover {
    color: #333;
}

.draggable-list .fa-arrows-alt {
    margin-right: 10px;
    color: #999;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
}

.card-tools {
    margin-left: auto;
}

.card-body.p-0 .draggable-list {
    margin: 0;
    padding: 0;
}
</style>
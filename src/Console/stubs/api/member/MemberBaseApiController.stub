<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    /** @var Model */
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 获取列表（分页）
     */
    public function lists(Request $request)
    {
        $pageSize = $request->get('pageSize', 10);
        $query = $this->model->query();

        // 动态过滤（如 ?name=John）
        foreach ($request->query() as $key => $value) {
            if (in_array($key, $this->model->getFillable())) {
                $query->where($key, $value);
            }
        }

        // 2. 动态排序（如 ?sort=created_at&order=desc）
        if ($request->has('sort')) {
            $sortField = $request->get('sort');
            $sortOrder = $request->get('order', 'asc'); // 默认升序

            // 验证字段是否可排序（防止 SQL 注入）
            if(!empty($this->getSortableFields())){
                if (in_array($sortField, $this->getSortableFields())) {
                    $query->orderBy($sortField, $sortOrder);
                }
            }else{
                $query->orderBy($sortField, $sortOrder);
            }

        }

        $items = $query->paginate($pageSize);
        return $this->returnData(0,1,$this->pageintes($items),'ok');
    }

    /**
     * 获取单条记录
     */
    public function show(int $id)
    {
        $info = $this->model->query()->findOrFail($id);
        return $this->returnData(0,1,['info'=> $info],'ok');
    }

    /**
     * 创建记录
     */
    public function store(Request $request)
    {
        $validationRules = $this->getValidationRules('store');
        $required = !empty($validationRules[0]) ? $validationRules[0]:[];
        $required_msg = !empty($validationRules[1]) ? $validationRules[1]:[];
        if(empty($required)){
            throw new \Exception('数据校验规则不能为空');
        }
        $data = $request->validate($required,$required_msg);
        $item = $this->model->query()->create($data);
        return $this->returnData(0,1,[],'ok');
    }

    /**
     * 更新记录
     */
    public function update(Request $request, int $id)
    {
        $validationRules = $this->getValidationRules('update');
        $required = !empty($validationRules[0]) ? $validationRules[0]:[];
        $required_msg = !empty($validationRules[1]) ? $validationRules[1]:[];
        if(empty($required)){
            throw new \Exception('数据校验规则不能为空');
        }

        $info = $this->model->query()->findOrFail($id);
        $data = $request->validate($required,$required_msg);
        $info->update($data);
        return $this->returnData(0,1,[],'ok');
    }

    /**
     * 批量删除
     *
     */
    protected function batchDestroy(Request $request)
    {
        try {
            \DB::beginTransaction();
            $ids = $request->input('ids');
            $deleted = $this->model->query()->whereIn('id', $ids)->delete();

            \DB::commit();
            return $this->returnData(0, 1, ['deleted_count' => $deleted], '批量删除成功');
        } catch (ValidationException $e) {
            \DB::rollBack();
            return $this->returnData(422, 0, ['errors' => $e->errors()], '批量删除失败: ' . $e->getMessage());
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->returnData(500, 0, [], '批量删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除记录
     */
    public function destroy(int $id)
    {
        $info = $this->model->query()->findOrFail($id);
        $info->delete();
        return $this->returnData(0,1,[],'ok');
    }

    /**
     * 定义可排序字段（子类可覆盖）
     */
    protected function getSortableFields(): array
    {
        return []; // 默认所有可填充字段可排序
    }

    /**
     * 定义验证规则（子类必须实现）
     */
    abstract protected function getValidationRules(string $action): array;

   /**
     * 获取所有字段名称
     * @param array $exclude 排除的字段
     */
    public function getFullFieldName($exclude = ['id','created_at','updated_at','deleted_at'])
    {
        $table = $this->model->getTable();
        $columns = [];
        // 获取所有字段及注释
        $fields = \DB::select("SHOW FULL COLUMNS FROM `{$table}`");
        foreach ($fields as $field) {
            if(!in_array($field->Field, $exclude)){
                $columns[] = $field->Field;
            }
        }
        $columns = array_unique($columns);
        return $columns;
    }

    /**
     * 应用过滤条件
     */
    protected function applyWhereCondition($query, $field, $operator, $value){
        switch ($operator) {
            case 'gt':
                $query->where($field, '>', $value);
                break;
            case 'gte':
                $query->where($field, '>=', $value);
                break;
            case 'lt':
                $query->where($field, '<', $value);
                break;
            case 'lte':
                $query->where($field, '<=', $value);
                break;
            case 'like':
                $query->where($field, 'LIKE', $value);
                break;
            case 'in':
                $query->whereIn($field, explode(',', $value));
                break;
            case 'between':
                $dates = explode(',', $value);
                if (count($dates) === 2) {
                    $query->whereBetween($field, $dates);
                }
                break;
            default:
                $query->where($field, $operator, $value);
        }
    }
    /**
     * 返回统一响应格式
     */
    public function returnCode($code = 0, $status = 'success', $msg = 'ok') {
        return [
            'code'   => $code,
            'status' => $status,
            'msg'    => $msg,
        ];
    }
    public function returnData($code = '', $status = '', $data = [], $msg = '') {
        if ($status == 1) {
            $status = 'success';
        } else {
            $status = 'error';
        }
        if (!is_array($data)) {
            $status = 'error';
            //$msg = '给予返回的数据不是一个数组';
        } else {
            if (count($data) == 0) {
                $data = (object)array();
            }
        }
        $tipstr = config('errorCode.' . $code);
        if ($msg != '') {
            $tipstr = $msg;
        }
        return response()->json([
            'code'   => $code,
            'status' => $status,
            'msg'    => $tipstr,
            'data'   => $data,
        ]);
    }
    /**
     * @desc 封装分页展示数据
     * author eRic
     * $Resource 过滤后的数据
     * $list_total 记录总数
     */
    public function pageintes($list,$pagesize = 20,$Resource = null,$list_total = ''){
        $page = 1;
        $total = 0;
        if($list instanceof LengthAwarePaginator){
            $items = $list->items();
            $total = $list->total();
            $page = $list->currentPage();
        }else{
            $items = $list;
            $total = count(collect($list)->toArray());
        }
        if(!empty($Resource)){
            $items = $Resource;
        }
        if(!empty($list_total)){
            $total =   $list_total;
        }
        $data['list'] = $items;
        $data['page_info'] = [
            'pagesize' => (int)$pagesize,
            'page' => $page,
            'total' => $total,
        ];
        return $data;
    }
}

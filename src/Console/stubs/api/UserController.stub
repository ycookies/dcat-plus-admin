<?php
namespace App\Admin\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminUser;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Group;

#[Group('用户','用户',5)]
class UserController extends BaseApiController
{

    public function __construct()
    {
        parent::__construct(new AdminUser());
    }

    /**
     * 获取列表
     *
     * 过滤条件，示例: name=shop&title[like]=%Laravel%&views[gt]=100&category[in]=1,2,3&created_at[between]=2023-01-01,2023-12-31 进行过滤
     */
    public function index(Request $request){
        return parent::lists($request);
    }

    /**
     * 获取单条记录
     */
    public function show(int $id)
    {
        return parent::show($id);
    }
    /**
     * 创建记录
     */
    public function store(Request $request)
    {
        return parent::store($request);
    }

     /**
     * 更新单个记录
     */
    public function update(Request $request, int $id)
    {
        return parent::update($request,$id);
    }

    /**
     * 删除单个记录
     */
    public function destroy($id){
        return parent::destroy($id);
    }

    /**
     * 批量更新
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchUpdate(Request $request)
    {

        $request->validate([
            /**
             * 要更新的id列表
             * @default [1,2]
             */
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:' . $this->model->getTable() . ',id'],
            /**
             * 更新数据
             * @default ['name':'杨光','email':'3664839@qq.com']','status':1]
             */
            'updateData' => ['required', 'array'],
            'updateData.*' => ['string', 'max:50'],
        ],[
            'ids.required' => 'ids不能为空',
            'ids.array' => 'ids必须是数组',
            'ids.min' => 'ids不能为空',
            'ids.*.integer' => 'ids必须是整数',
            'ids.*.exists' => 'ids不存在',
            'updateData.required' => 'updateData不能为空',
            'updateData.array' => 'updateData必须是数组',
            'updateData.*.string' => 'updateData必须是字符串',
            'updateData.*.max' => 'updateData不能超过50个',
        ]);

        $ids = $request->input('ids');
        $data = $request->input('updateData');
        $updated = $this->model->query()->whereIn('id', $ids)->update($data);


        return $this->returnData(0, 1, ['updated_count' => $updated], 'Batch update completed successfully');

    }

    /**
     * 批量删除
     *
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            /**
             * 要删除的id列表
             * @default [1,2]
             */
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:' . $this->model->getTable() . ',id'],
        ],[
            'ids.required' => 'ids不能为空',
            'ids.array' => 'ids必须是数组',
            'ids.min' => 'ids不能为空',
            'ids.*.integer' => 'ids必须是整数',
            'ids.*.exists' => 'ids不存在',
        ]);
        return parent::batchDestroy($request);
    }

    /**
     * 下载导入模板文件
     *
     */
    public function downImportTplFile(Request $request)
    {
        return parent::downImportTplFile($request);
    }

    /**
     * 导入数据(暂不可用)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request)
    {

            $request->validate([
                /**
                 * 文件
                 * @default 示例文件
                 */
                'file' => 'required|file|mimes:xlsx,xls,csv|max:100240', // 100MB max
            ]);

            \DB::beginTransaction();

            $file = $request->file('file');
            $importedCount = 0;
            $errors = [];

            // 使用匿名类实现导入功能
            $import = new class($this->model, $importedCount, $errors) implements \Maatwebsite\Excel\Concerns\ToModel, \Maatwebsite\Excel\Concerns\WithHeadingRow {
                private $model;
                private $importedCount;
                private $errors;

                public function __construct($model, &$importedCount, &$errors) {
                    $this->model = $model;
                    $this->importedCount = &$importedCount;
                    $this->errors = &$errors;
                }

                public function model(array $row) {
                    try {
                        // Map Excel columns to model fields
                        $data = [
                            // Map your Excel columns to model fields here
                            // 'name' => $row['name'] ?? $row['名称'] ?? null,
                            // 'email' => $row['email'] ?? $row['邮箱'] ?? null,
                            // 'status' => $row['status'] ?? $row['状态'] ?? 1,
                        ];

                        // Remove empty values
                        $data = array_filter($data, function($value) {
                            return $value !== null && $value !== '';
                        });

                        if (!empty($data)) {
                            $this->importedCount++;
                            return new $this->model($data);
                        }

                        return null;
                    } catch (\Exception $e) {
                        $this->errors[] = "Row error: " . $e->getMessage();
                        return null;
                    }
                }
            };

            \Maatwebsite\Excel\Facades\Excel::import($import, $file);

            return $this->returnData(0, 1, [
                'imported_count' => $importedCount,
                'errors' => $errors
            ], 'Import completed successfully');

    }

    /**
     * 导出数据
     *
     */
    public function export(Request $request)
    {
        // 校验请求参数
        $request->validate([
            /**
             * 导出格式
             * @default xlsx
             */
            'format' => ['required', 'string', 'in:xlsx,csv'],
            /**
             *
             * 过滤条件
             * @default [['field'=>'name','op'=>'like','value'=>'杨光']]
             */
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['required_with:filters', 'string', 'max:50'],
            'filters.*.op' => ['required_with:filters', 'string', 'in:=,>,<,like,in'],
            'filters.*.value' => ['required_with:filters'],
            /**
             * 排序字段
             * @default id
             */
            'sort_field' => ['nullable','string'],
            /**
             * 排序方式
             * @default asc
             */
            'sort_order' => ['required_with:sort_field', 'string', 'in:asc,desc'],
            /**
             * 导出字段
             * @default ['id','name','email','status']
             */
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string', 'max:50'],
        ],[
            'format.required' => '导出格式不能为空',
            'format.string' => '导出格式必须是字符串',
            'format.in' => '导出格式必须是xlsx或csv',
            'filters.array' => '过滤条件必须是数组',
            'filters.*.field.required_with' => '过滤字段不能为空',
            'filters.*.field.string' => '过滤字段必须是字符串',
            'filters.*.op.required_with' => '操作符不能为空',
            'filters.*.op.in' => '操作符只支持=, >, <, like, in',
            'filters.*.value.required_with' => '过滤值不能为空',
            'sort_field.string' => '排序字段必须是字符串',
            'sort_order.required_with' => '排序字段不能为空',
            'sort.order.in' => '排序方式只支持asc,desc',
            'fields.array' => '字段参数必须是数组',
            'fields.*.string' => '字段名必须是字符串',
        ]);

        $sortableFields = $this->getSortableFields();
        $sortField = $request->get('sort_field');
        if (!empty($sortField) && !in_array($sortField, $sortableFields)) {
            throw new \Dcat\Admin\Exception\ApiException('排序字段不支持');
        }
        return parent::exportData($request);
    }

    /**
     * 获取所有字段及字段注释
     *
     * @return JsonResponse
     */
    public function field(Request $request)
    {
        return parent::field($request);
    }

    /**
     * 定义可排序字段
     *
     * @return array
     */
    protected function getSortableFields(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            // Add more sortable fields as needed
        ];
    }

    /**
     * @desc 数据校验证规则
     * @param string $action 操作类型（store 创建数据，update 更新数据）
     * @return array
     */
    protected function getValidationRules(string $action): array
    {
        return [
                   'store' => [
                       [
                           'username' => 'required',
                           'password' => 'required',
                           'avatar' => 'required',
                       ],
                       [
                           'factory_code.required' => '加工厂编码不能为空',
                           'factory_name.required' => '加工厂名称不能为空',
                       ]
                   ],
                   'update' => [],
               ][$action] ?? [];
    }

}

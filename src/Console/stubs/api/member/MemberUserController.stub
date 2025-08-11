<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\MemberUser;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Group;

#[Group('用户','用户',2)]
class MemberUserController extends BaseApiController
{

    public function __construct()
    {
        parent::__construct(new MemberUser());
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
        public function show($id){
            $msg            = \Validator::make(['id'=> $id], [
                /**
                 * ID
                 * @default 1
                 */
                'id' => ['required', 'integer', 'min:1','exists:' . $this->model->getTable() . ',id'],
            ],[
                'id.required' => 'ID不能为空',
                'id.integer'  => 'ID必须为整数',
                'id.exists'   => '此ID不存在',
            ]);

            // 处理异常验证
            if ($msg->fails()) {
                $errors = $msg->errors()->first();
                throw new \Dcat\Admin\Exception\ApiException($errors);
            }

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
                           'username.required' => '用户名不能为空',
                           'password.required' => '密码不能为空',
                       ]
                   ],
                   'update' => [],
               ][$action] ?? [];
    }

}

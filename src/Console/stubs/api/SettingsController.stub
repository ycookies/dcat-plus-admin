<?php
namespace App\Admin\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Dcat\Admin\Models\Setting;

use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Group;

#[Group('设置','设置',6)]
class SettingsController extends BaseApiController
{

    public function __construct()
    {
        parent::__construct(new Setting());
    }


    /**
     * 获取列表
     */
    public function index(Request $request){
        return parent::lists($request);
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
                       [],
                       []
                   ],
                   'update' => [],
               ][$action] ?? [];
    }
}

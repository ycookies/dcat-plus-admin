<?php
namespace App\Admin\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Group;

#[Group('授权','授权',1)]
class AuthController extends Controller
{
    /**
     * 管理员登陆
     * @unauthenticated
     */
    public function login(Request $request){
        $request->validate(
            [
                /**
                 * 用户名
                 * @default admin
                 */
                'username' => ['required','string','min:1'],
                /**
                 * 登陆密码
                 * @default admin
                 */
                'password' => 'required',
            ], [
                'username.required' => '请填写用户名',
                'password.required' => '请填写密码',
            ]
        );

        $username = $request->get('username');
        $password = $request->get('password');

        $adminModel = '\App\Models\AdminUser';

        $center = $adminModel::where(['username'=>$username])->first(); // 'is_active'=>1

        if(empty($center)){
            return response()->json([
                'code'  => 500,
                'msg'   => '账户信息不存在'
            ]);
        }
        if($center->is_active != 1){
            return response()->json([
                'code'  => 500,
                'msg'   => '该账户未激活'
            ]);
        }
        if(!Hash::check($password, $center->password)){
            return [
                'code'  => 500,
                'msg'   => '账户密码不正确'
            ];
        }

        if (!$token = auth('adminapi')->fromUser($center)) {
            return response()->json([
                'code'  => 500,
                'msg'   => '登录失败',
            ]);
        }
        $data = [
            'access_token'  => $token,
            'token_type'    => 'bearer',
            'expires_in'    => auth('adminapi')->factory()->getTTL() * 60 * 24 * 7,
            'username'      => $center->username,
        ];
        return response()->json(['code'=>0,'msg'=>'ok','data'=>$data]);
    }

    /**
     * 管理员退出登陆
     * @unauthenticated
     */
    public function logout(Request $request){
        auth('adminapi')->logout();
        return response()->json([
            'code'  => 0,
            'msg'   => '退出成功'
        ]);
    }


}

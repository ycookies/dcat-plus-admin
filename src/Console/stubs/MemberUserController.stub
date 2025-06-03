<?php

namespace App\Admin\Controllers;

use App\Models\MemberUser;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Admin;

class MemberUserController extends AdminController
{
    /**
     * page index
     */
    public function index(Content $content)
    {
        return $content
            ->header('用户管理')
            ->description('全部')
            ->breadcrumb(['text'=>'列表','url'=>''])
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MemberUser(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('avatar','头像')->image('', 44, 44);
            $grid->column('username');
            $grid->column('phone');
            $grid->column('email');
            $grid->column('status')->using(MemberUser::$status_arr);
            $grid->column('balance');
            $grid->column('junior_at');
            $grid->column('created_at');
            //$grid->column('updated_at')->sortable();
            $grid->setActionClass(Grid\Displayers\Actions::class); // 行操作按钮显示方式 图标方式
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // $actions->disableDelete(); //  禁用删除
                $actions->disableEdit();   //  禁用修改
                // $actions->disableQuickEdit(); //禁用快速修改(弹窗形式)
                // $actions->disableView(); //  禁用查看
            });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new MemberUser(), function (Show $show) {
            $show->field('id');
            $show->field('username');
            $show->field('phone');
            $show->field('email');
            $show->field('lastLoginTime');
            $show->field('lastLoginIp');
            $show->field('phoneVerified');
            $show->field('emailVerified');
            $show->field('avatar');
            $show->field('avatarMedium');
            $show->field('avatarBig');
            $show->field('gender');
            $show->field('realname');
            $show->field('signature');
            $show->field('vipId');
            $show->field('vipExpire');
            $show->field('nickname');
            $show->field('status');
            $show->field('balance');
            $show->field('freeze_price');
            $show->field('groupId');
            $show->field('deleteAtTime');
            $show->field('isDeleted');
            $show->field('messageCount');
            $show->field('registerIp');
            $show->field('is_certified');
            $show->field('parent_id');
            $show->field('temp_parent_id');
            $show->field('junior_at');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new MemberUser(), function (Form $form) {
            $form->display('id');
            $id = $form->getKey();
            $form->text('username')->required();
            $form->text('phone')->required();
            $form->text('email');
            $form->image('avatar');
            if ($id) {
                $form->password('password', trans('admin.password'))
                    ->minLength(5)
                    ->maxLength(20)
                    ->customFormat(function () {
                        return '';
                    });
            } else {
                $form->password('password', trans('admin.password'))
                    ->required()
                    ->minLength(5)
                    ->maxLength(20);
            }
            $form->password('password_confirmation','确认密码')
                ->same('password')
                ->required();

            $form->ignore(['password_confirmation']);
            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->get('password') != $form->password) {
                    $form->password = bcrypt($form->password);
                }
                if (! $form->password) {
                    $form->deleteInput('password');
                }
            });
        });
    }
}

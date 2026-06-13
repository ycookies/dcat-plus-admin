<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Grid;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\OperationLog;
use Dcat\Admin\OperationLog\OperationLogServiceProvider;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Arr;

class OperationLogController
{
    public function index(Content $content)
    {
        return $content
            ->title(trans('operation-log.title'))
            ->description(trans('admin.list'))
            ->body($this->grid());
    }

    protected function grid()
    {
        return new Grid(OperationLog::with('user'), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('user', trans('admin.user'))
                ->display(function ($user) {
                    if (! $user) {
                        return;
                    }

                    $user = Helper::array($user);

                    return $user['name'] ?? ($user['username'] ?? $user['id']);
                })
                ->link(function () {
                    if ($this->user) {
                        return admin_url('auth/users/'.$this->user['id']);
                    }
                });

            $grid->column('method', trans('admin.method'))
                ->label(OperationLog::$methodColors)
                ->filterByValue();

            $grid->column('path', trans('admin.uri'))->display(function ($v) {
                return "<code>$v</code>";
            })->filterByValue();

            $grid->column('ip', 'IP')->filterByValue();

            $grid->column('input')->display(function ($input) {
                $input = json_decode($input, true);

                if (empty($input)) {
                    return;
                }

                $input = Arr::except($input, ['_pjax', '_token', '_method', '_previous_']);

                if (empty($input)) {
                    return;
                }

                return '<pre class="dump" style="max-width: 500px">'.json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>';
            });

            $grid->column('created_at', trans('admin.created_at'));

            $grid->model()->orderBy('id', 'DESC');

            $grid->disableCreateButton();
            $grid->disableQuickEditButton();
            $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->showColumnSelector();
            $grid->setActionClass(Grid\Displayers\Actions::class);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $userModel = config('admin.database.users_model');

                $filter->in('user_id', trans('admin.user'))
                    ->multipleSelect($userModel::pluck('name', 'id'))->width(3);

                $filter->equal('method', trans('admin.method'))
                    ->select(
                        array_combine(OperationLog::$methods, OperationLog::$methods)
                    )->width(3);

                $filter->like('path', trans('admin.uri'))->width(3);
                $filter->equal('ip', 'IP')->width(3);
                $filter->between('created_at')->datetime()->width(5);
            });
        });
    }

    public function destroy($id)
    {
        $ids = explode(',', $id);

        OperationLog::destroy(array_filter($ids));

        return JsonResponse::make()
            ->success(trans('admin.delete_succeeded'))
            ->refresh()
            ->send();
    }
}

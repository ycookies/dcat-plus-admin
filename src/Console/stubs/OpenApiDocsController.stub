<?php
namespace App\Admin\Controllers;

use Illuminate\Routing\Controller;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Admin;

// 开放接口文档
class OpenApiDocsController extends Controller {

    /**
     * page index
     */
    public function index(Content $content)
    {
        return $content
            ->header('开放接口文档')
            ->description('全部')
            ->breadcrumb(['text'=>'列表','url'=>''])
            ->body($this->viewHtml());
    }



    /**
     * @desc
     * author eRic
     * dateTime 2025-06-14 15:05
     */
    protected function viewHtml(){
        $admin_api_path = config('admin.openapi.admin-api.api_path','admin-api');
        $member_api_path = 'api';
        $htmls = <<<HTML

<div class="row">
    <div class="col-md-3">
        <div class="card h-100 book-card">
                    <img src="/vendor/dcat-admin/images/admin-api-logo.jpg"
                         class="card-img-top book-cover">
                    <div class="card-body">
                        <span class="badge bg-primary mb-2">管理端</span>
                        <h5 class="card-title book-title">B端接口文档</h5>
                        <p class="card-text book-author">已集成授权模块</p>

                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="book-price text-success fw-bold">免费</span>
                            <a href="/docs/$admin_api_path" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fa fa-eye me-1"></i>查看
                            </a>
                        </div>
                    </div>
        </div>
    </div>
   <div class="col-md-3">
        <div class="card h-100 book-card">
                    <img src="/vendor/dcat-admin/images//member-api-logo.jpg"
                         class="card-img-top book-cover">
                    <div class="card-body">
                        <span class="badge bg-success mb-2">用户端</span>
                        <h5 class="card-title book-title">C端接口文档</h5>
                        <p class="card-text book-author">已集成授权模块</p>

                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="book-price text-success fw-bold">免费</span>
                            <a href="/docs/$member_api_path" target="_blank" class="btn bg-success btn-sm">
                                <i class="fa fa-eye me-1"></i>查看
                            </a>
                        </div>
                    </div>
        </div>
    </div>
</div>
HTML;
return $htmls;
    }
}
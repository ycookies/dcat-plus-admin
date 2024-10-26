<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Tab;
use Illuminate\Routing\Controller;

class IconController extends Controller
{
    public function index(Content $content)
    {
        Admin::style('.icon-list-demo div {
            cursor: pointer;
            line-height: 45px;
            white-space: nowrap;
            color: #75798B;
        }.icon-list-demo i {
            display: inline-block;
            font-size: 18px;
            margin: 0;
            vertical-align: middle;
            width: 40px;
        }');

        return $content->title('Icon')->body(function (Row $row) {
            $tab = Tab::make()
                ->withCard()
                ->padding('20px')
                ->add(('Feather'), view('admin::helpers.feather'),'','icon-Feather')
                ->add(('Font Awesome'), view('admin::helpers.font-awesome'),'','icon-Font-Awesome')
                ->add('dcat 内置字体颜色', view('admin::helpers.font-color'),'','icon-Font-Color');

            $row->column(12, $tab);
        });
    }
}

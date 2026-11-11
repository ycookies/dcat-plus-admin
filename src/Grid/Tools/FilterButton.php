<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Filter;
use Illuminate\Support\Str;

class FilterButton extends AbstractTool
{
    /**
     * @var string
     */
    protected $view = 'admin::filter.button';

    /**
     * @var string
     */
    protected $btnClassName;

    /**
     * @return \Dcat\Admin\Grid\Filter
     */
    protected function filter()
    {
        return $this->parent->filter();
    }

    /**
     * Get button class name.
     *
     * @return string
     */
    protected function getElementClassName()
    {
        if (! $this->btnClassName) {
            $this->btnClassName = 'filter-btn-'.Str::random(8);
        }

        return $this->btnClassName;
    }

    /**
     * Set up script for filter button.
     */
    protected function addScript()
    {
        $filter = $this->filter();
        $id = $filter->filterID();

        if ($filter->mode() === Filter::MODE_RIGHT_SIDE) {
            if ($filter->grid()->model()->getCurrentPage() > 1) {
                $expand = 'false';
            } else {
                $expand = $filter->expand ? 'true' : 'false';
            }

            $script = <<<JS
(function () {
    var slider,
        expand = {$expand};

     function initSlider() {
        slider = new Dcat.Slider({
            target: '#{$id}',
        });

        slider
            .\$container
            .find('.right-side-filter-container .header')
            .width(slider.\$container.width() - 20);

        expand && setTimeout(slider.open.bind(slider), 10);
    }

    expand && setTimeout(initSlider, 10);

    $('.{$this->getElementClassName()}').on('click', function () {
        if (! slider) {
            initSlider()
        }
        slider.toggle();

        return false
    });

    // 注意：不能在这里对 .modal 内点击无差别 stopPropagation——那会掐断冒泡，
    // document 上的 Bootstrap data-api 委托（[data-toggle="modal"]）收不到
    // 弹窗内按钮的点击，导致"弹窗内再打开另一个弹窗"的按钮全部失灵。
    // 改为在关闭筛选面板的判断里排除 .modal 内的点击。
    $(document).on('click', '.wrapper', function (e) {
        if (slider && slider.close && !$(e.target).closest('.modal').length) {
            slider.close();
        }
    });
})();
JS;
        } else {
            $script = <<<JS
$('.{$this->getElementClassName()}').on('click', function(){
    $('#{$id}').parent().toggleClass('d-none');
});
JS;
        }

        Admin::script($script);
    }

    /**
     * @return mixed
     */
    protected function renderScopes()
    {
        return $this->filter()->scopes()->map->render()->implode("\r\n");
    }

    /**
     * Get label of current scope.
     *
     * @return string
     */
    protected function currentScopeLabel()
    {
        if ($scope = $this->filter()->getCurrentScope()) {
            return "&nbsp;{$scope->getLabel()}&nbsp;";
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $filter = $this->filter();

        $scopres = $filter->scopes();
        $filters = $filter->filters();
        $valueCount = $filter->countConditions();

        if ($scopres->isEmpty() && ! $filters) {
            return;
        }

        $this->addScript();

        $onlyScopes = ((! $filters || $this->parent->option('filter') === false) && ! $scopres->isEmpty()) ? true : false;

        $variables = [
            'scopes'        => $scopres,
            'current_label' => $this->currentScopeLabel(),
            'url_no_scopes' => $filter->urlWithoutScopes(),
            'btn_class'     => $this->getElementClassName(),
            'expand'        => $filter->expand,
            'filter_text'   => true,
            'only_scopes'   => $onlyScopes,
            'valueCount'    => $valueCount,
        ];

        return view($this->view, $variables)->render();
    }
}

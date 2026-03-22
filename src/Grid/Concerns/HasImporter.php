<?php

namespace Dcat\Admin\Grid\Concerns;

use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Importer;
use Dcat\Admin\Grid\Importers\AbstractImporter;
use Dcat\Admin\Grid\Tools;
use Dcat\Admin\Http\Forms\GirdImportFrom;
use Dcat\Admin\Widgets\Modal;

trait HasImporter
{
    /**
     * @var Importer
     */
    protected $importer;

    /**
     * @var bool
     */
    protected $enableImporter = false;

    /**
     * @var bool
     */
    protected $exported = false;

    /**
     * 暂不可用,未实现
     * Set importer driver for Grid to import.
     *
     * @param  string|Grid\Importers\AbstractImporter|array  $importerDriver
     * @return Importer
     */
    public function import($importerDriver = null)
    {
        $this->enableImporter = true;
        $titles = [];

        if (is_array($importerDriver) || $importerDriver === false) {
            $titles = $importerDriver;
            $importerDriver = null;
        }

        $importer = $this->importer();

        if ($importerDriver) {
            $importer->resolve($importerDriver);
        }

        return $titles ? $importer->titles($titles) : $importer;
    }
    
    // 处理导入
    public function handle(){
        return 111;
    }

    /**
     * Handle export request.
     *
     * @param  bool  $forceImport
     * @return mixed
     */
    public function handleImportRequest($forceImport = false)
    {
        if (
            $this->exported
            || (
                (! $this->allowImporter()
                    || ! $scope = request($this->importer()->getQueryName()))
                && ! $forceImport
            )
        ) {
            return;
        }

        $this->exported = true;

        $this->callBuilder();

        $this->fire(new Grid\Events\Importing([$scope]));

        // clear output buffer.
        if (ob_get_length()) {
            ob_end_clean();
        }

        if ($forceImport || $this->allowImporter()) {
            return $this->resolveImportDriver($scope)->export();
        }
    }

    /**
     * @return Importer
     */
    public function importer()
    {
        return $this->importer ?: ($this->importer = new Importer($this));
    }

    /**
     * @param  string  $scope
     * @return AbstractImporter
     */
    protected function resolveImportDriver($scope)
    {
        return $this->importer()->driver()->withScope($scope);
    }

    /**
     * Get the export url.
     *
     * @param  int  $scope
     * @param  null  $args
     * @return string
     */
    public function importUrl($scope = 1, $args = null)
    {
        $input = array_merge(request()->all(), $this->importer()->formatImportQuery($scope, $args));

        if ($constraints = $this->model()->getConstraints()) {
            $input = array_merge($input, $constraints);
        }

        return $this->resource().'?'.http_build_query($input);
    }

    /**
     * Render export button.
     *
     * @return string
     */
    public function renderImportButton()
    {
        if (! $this->allowImporter()) {
            return '';
        }
        $import_tpl_url = $this->resource().'?_export_=field';//$this->grid->importUrl('selected', '__rows__');
        $import = trans('admin.import');
        $html = <<<HTML
<button type="button" class="btn btn-success">
        <i class="feather icon-upload"></i>
        <span class="d-none d-sm-inline">&nbsp;{$import}&nbsp</span>
    </button>
HTML;


        $model_name = base64_encode(get_class($this->model()->repository()->model()));
        $modal = Modal::make()
            ->lg()
            ->title(trans('admin.import_data'))
            ->body(GirdImportFrom::make()->payload(['model'=>$model_name,'table_titles'=> '1','import_tpl_url'=>$import_tpl_url]))
            ->button($html);
        
        return $modal->render();
    }
    /**
     * If grid show export btn.
     *
     * @return bool
     */
    public function allowImporter()
    {
        return $this->enableImporter;
    }
}

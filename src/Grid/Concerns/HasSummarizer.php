<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid;
use Illuminate\Contracts\Support\Renderable;

trait HasSummarizer
{

    // Disable  Summarizers
    public function disableSummarizers(bool $disable = true) {
        $this->option('summarizers', ! $disable);
        return $this;
    }

    // Disable  Summarizer this page
    public function disableSummarizerThisPage(bool $disable = true) {

        return $this;
    }

    // Disable  Summarizer all page
    public function disableSummarizerAllPage(bool $disable = true) {

        return $this;
    }
    // 获取汇总器状态
    public function showSummarizerStatus(bool $bool = true) {
        $this->option('summarizers', $bool);
        return $this;
    }
    
    // 获取汇总器状态
    public function getSummarizerStatus() {
        return $this->options['summarizers'];
    }

    // 获取汇总器当页 状态
    public function getSummarizerThisPageStatus(){
        return true;
    }

    // 获取汇总器 全部页状态
    public function getSummarizerAllPageStatus(){
        return false;
    }
}

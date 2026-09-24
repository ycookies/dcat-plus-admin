<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

class Textarea extends Field
{
    /**
     * Default rows of textarea.
     *
     * @var int
     */
    protected $rows = 5;

    /**
     * @var bool rows 是否为默认值（用户未显式调用 rows()）
     */
    protected $rowsIsDefault = true;

    /**
     * Set rows of textarea.
     *
     * @param  int  $rows
     * @return $this
     */
    public function rows($rows = 5)
    {
        $this->rows = $rows;
        $this->rowsIsDefault = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultRows(): bool
    {
        return $this->rowsIsDefault;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        if (is_array($this->value)) {
            $this->value = json_encode($this->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $this->addVariables(['rows' => $this->rows]);

        return parent::render();
    }
}

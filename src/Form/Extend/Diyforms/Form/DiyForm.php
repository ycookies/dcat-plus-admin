<?php

namespace Dcat\Admin\Form\Extend\Diyforms\Form;

use Dcat\Admin\Admin;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Widgets\Alert;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Support\JavaScript;

class DiyForm extends Field
{
	protected $view = 'admin::form.extend.diy-form.form';
	
	/**
	 * 主题色
	 *
	 * @var string
	 */
	protected $theme_color = '';

	/**
	 * 组件类型
	 *
	 * @var array
	 */
	protected $component_type = [];

	/**
	 * 额外的预览html代码
	 *
	 * @var string
	 */
	protected $preview_html = '';

	public function __construct($column, $arguments = [])
	{
		$this->initCompontenType();
		$this->theme_color = Admin::color()->primary();

		parent::__construct($column, $arguments);
	}

	/**
	 * 初始化组件类型
	 */
	protected function initCompontenType()
	{
		$this->component_type = [
			[
				'name'          => '输入框',
				'type'          => 'input',
				'label'         => '',
				'required'      => 0,
				'default_value' => '',
			],
			[
				'name'             => '文本域',
				'type'             => 'textarea',
				'label'            => '',
				'required'         => 0,
				'props_items'      => [
					[
						'label' => '显示行',
						'bind'  => 'rows',
					],
				],
				'default_value'    => '',
				'rows'             => 3,
				'validate_handler' => JavaScript::make(<<<JS
(data) => {
    if(!data.rows){
        return '请输入显示行'
    }
}
JS

				),
			],
			[
				'name'          => '单选',
				'type'          => 'radio',
				'label'         => '',
				'required'      => 0,
				'default_value' => '',
				'options'       => [
					'label'  => '选项',
					'values' => [''],
				],
			],
			[
				'name'          => '多选',
				'type'          => 'checkbox',
				'label'         => '',
				'required'      => 0,
				'default_value' => '',
				'options'       => [
					'label'  => '选项',
					'values' => [''],
				],
			],
			[
				'name'          => '下拉列表',
				'type'          => 'select',
				'label'         => '',
				'required'      => 0,
				'default_value' => '',
				'options'       => [
					'label'  => '选项',
					'values' => [''],
				],
			],
			[
				'name'     => '图片上传',
				'type'     => 'upload-image',
				'label'    => '',
				'required' => 0,
			],
			[
				'name'          => '视频上传',
				'type'          => 'upload-vedio',
				'label'         => '',
				'required'      => 0,
				'default_value' => '',
			],
		];
	}

	/**
	 * 移除组件类型
	 *
	 * @param $type
	 *
	 * @return $this
	 */
	public function subComponentType($type)
	{
		$types = $this->component_type;

		foreach ($types as $k => $v) {
			if ($v['type'] == $type) {
				unset($types[$k]);
			}
		}

		$this->component_type = $types;

		return $this;
	}

	/**
	 * 移除多个组件类型
	 *
	 * @param $types
	 *
	 * @return $this
	 */
	public function subComponentTypes($types)
	{
		foreach ($types as $item) {
			$this->subComponentType($item);
		}
		return $this;
	}

	/**
	 * 添加组件类型
	 *
	 * @param $item
	 *
	 * @return $this
	 */
	public function addComponentType($item)
	{
		$needs = ['name', 'type', 'label', 'required', 'default_value'];
		$keys  = array_keys($item);

		foreach ($needs as $need) {
			if (!in_array($need, $keys)) {
				admin_exit(
					Content::make()
						->body(Alert::make("缺少必须项 {$need}", 'Dcat Diy Form Error')->danger())
				);
			}
		}

		array_push($this->component_type, $item);

		return $this;
	}

	/**
	 * 添加多个组件类型
	 *
	 * @param $items
	 *
	 * @return $this
	 */
	public function addComponentTypes($items)
	{
		foreach ($items as $item) {
			$this->addComponentType($item);
		}

		return $this;
	}

	/**
	 * 自定义主题色
	 *
	 * @param string $color
	 *
	 * @return $this
	 */
	public function themeColor($color = '')
	{
		$this->theme_color = $color;

		return $this;
	}

	/**
	 * 添加预览html
	 *
	 * @param $html
	 *
	 * @return $this
	 */
	public function addPreviewHtml($html)
	{
		$this->preview_html .= $html;

		return $this;
	}

	public function render()
	{
		if (!$this->shouldRender()) {
			return '';
		}

		$this->addVariables([
			'component_type' => json_encode($this->component_type),
			'theme_color'    => $this->theme_color,
			'preview_html'   => $this->preview_html,
		]);

		$this->setDefaultClass();

		$this->callComposing();

		$this->withScript();

		return Admin::view($this->view(), $this->variables());
	}
}

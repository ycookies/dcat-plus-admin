<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Support\Str;

class Calendar extends Widget {

    public static $js = [
        '@fullcalendar'
    ];

    public static $css = [
        '@fullcalendar'
    ];
    /**
     * @var string
     */
    protected $view = 'admin::widgets.calendar';

    protected $calendarId;
    /**
     * @var array
     */
    protected $items = [];

    protected $locale = 'zh-cn';

    protected $timeZone = 'Asia/Shanghai';

    protected $initialView = 'dayGridMonth';

    protected $header_toolbar_right_btn = 'dayGridMonth,timeGridWeek,timeGridDay,listWeek';

    /**
     * Collapse constructor.
     */
    public function __construct() {
        $this->calendarId = 'calendar' . Str::random(5);
        $this->id('calendar-' . uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    /**
     * @desc 设置 日历容器ID
     * @param $id
     */
    public function calendarId($id) {
        $this->calendarId = $id;
        return $this;
    }

    /**
     * @desc 设置 日历语言包
     * @param $value
     */
    public function locale($value) {
        $this->locale = $value;
        return $this;
    }

    /**
     * @desc 设置 日历的时区
     * @param $value
     */
    public function timeZone($value) {
        $this->timeZone = $value;
        return $this;
    }

    /**
     * @desc 设置 日历的初始化视图  可选值 dayGridMonth,timeGridWeek,timeGridDay,listWeek
     * @param $value
     */

    public function initialView($value) {
        $this->initialView = $value;
        return $this;
    }

    /**
     * @desc 设置 日历的头部右侧所展示的btn  全部：dayGridMonth,timeGridWeek,timeGridDay,listWeek
     * @param $value
     */
    public function headerToolbarRightBtn($value) {
        $this->header_toolbar_right_btn = $value;
        return $this;
    }

    /**
     * @desc 设置 日历的日期事件
     * @param $value
     */
    public function eventItem(array $item) {
        $this->items = $item;
        return $this;
    }

    /**
     * Add item.
     * 单个增加日历的日期事件
     * @param string $title 标题
     * @param string $title 描述
     * @param string $start 开始日期
     * @param string $end   结束日期
     * @return $this
     */
    public function addEvents($title, $description = '', $start, $end = '') {
        $event_info = [
            'title'       => $title,
            'description' => $description,
            'start'       => $start,
            'allDay'      => false,
            'showModal'   => true,
        ];
        if (!empty($end)) {
            $event_info['end'] = $end;
        }
        $this->items[] = $event_info;

        return $this;
    }
    // 
    public function backgroundColor($color) {
        if(!empty($this->items[count($this->items) - 1])){
            $this->items[count($this->items) - 1]['backgroundColor'] = $color;
        }
        return $this;
    }

    public function borderColor($color) {
        if(!empty($this->items[count($this->items) - 1])){
            $this->items[count($this->items) - 1]['borderColor'] = $color;
        }
        
        return $this;
    }

    public function allDay($bool) {
        if(!empty($this->items[count($this->items) - 1])){
            $this->items[count($this->items) - 1]['allDay'] = $bool;
        }
        
        return $this;
    }

    /**
     * @desc 设置 日历的日期事件是否 点击 展示modal
     * @param $value
     */
    public function showModal($bool = true) {
        if(!empty($this->items[count($this->items) - 1])){
            $this->items[count($this->items) - 1]['showModal'] = $bool;
        }
        
        return $this;
    }

    /**
     * @desc 设置 日历的日期事件的weburl
     * @param $value
     */
    public function webUrl($url) {
        if(!empty($this->items[count($this->items) - 1])){
            $this->items[count($this->items) - 1]['url'] = $url;
        }
        
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function defaultVariables() {

        return [
            'id'                       => $this->id,
            'locale'                   => $this->locale,
            'timeZone'                 => $this->timeZone,
            'initialView'              => $this->initialView,
            'header_toolbar_right_btn' => $this->header_toolbar_right_btn,
            'calendar_id'              => $this->calendarId,
            'items'                    => json_encode($this->items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'attributes'               => $this->formatAttributes(),
        ];
    }

}

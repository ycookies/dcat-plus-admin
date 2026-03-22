<?php

namespace Dcat\Admin\Form\Extend\FormMedia\Form\Field;

use Dcat\Admin\Form\Extend\FormMedia\Form\Field;

/**
 * 表单视频字段
 *
 * @create 2020-11-25
 * @author deatil
 */
class Video extends Field
{
    protected $limit = 1;
    
    protected $remove = true;
    
    protected $type = 'video';
}

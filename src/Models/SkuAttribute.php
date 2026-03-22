<?php

namespace Dcat\Admin\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class SkuAttribute extends Model
{
    use HasDateTimeFormatter;

    public  $table = 'sku_attribute';
    public  $guarded = [];
    public static $attrType = [
        'radio' => '单选',
        'checkbox' => '多选',
    ];

    protected $casts = [
        'attr_value' => 'json'
    ];
}

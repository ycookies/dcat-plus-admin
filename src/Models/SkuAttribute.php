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
        'checkbox' => '复选框',
        'radio' => '单选框',
    ];

    protected $casts = [
        'attr_value' => 'json'
    ];
}

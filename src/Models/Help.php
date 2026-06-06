<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class Help extends Model
{
    protected $table = 'admin_helps';

    protected $fillable = ['category_id', 'title', 'content', 'link', 'link_target', 'sort', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }
}
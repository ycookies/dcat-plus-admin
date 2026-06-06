<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class HelpCategory extends Model
{
    protected $table = 'admin_help_categories';

    protected $fillable = ['name', 'icon', 'sort', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function helps()
    {
        return $this->hasMany(Help::class, 'category_id')->where('is_active', true)->orderBy('sort');
    }

    public function allHelps()
    {
        return $this->hasMany(Help::class, 'category_id')->orderBy('sort');
    }
}
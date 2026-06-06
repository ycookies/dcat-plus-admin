<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    protected $table = 'admin_notification_reads';

    public $timestamps = false;

    protected $fillable = ['notification_id', 'admin_user_id', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}
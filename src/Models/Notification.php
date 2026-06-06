<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = ['title', 'content', 'type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reads()
    {
        return $this->hasMany(NotificationRead::class, 'notification_id');
    }

    /**
     * 获取指定用户未读的通知
     */
    public static function getUnreadForUser($adminUserId)
    {
        return static::where('is_active', true)
            ->whereDoesntHave('reads', function ($q) use ($adminUserId) {
                $q->where('admin_user_id', $adminUserId);
            })
            ->orderByDesc('id')
            ->get();
    }

    /**
     * 获取指定用户未读通知数量
     */
    public static function getUnreadCountForUser($adminUserId)
    {
        return static::where('is_active', true)
            ->whereDoesntHave('reads', function ($q) use ($adminUserId) {
                $q->where('admin_user_id', $adminUserId);
            })
            ->count();
    }

    /**
     * 获取指定用户的全部通知（含已读状态）
     */
    public static function getAllForUser($adminUserId)
    {
        return static::where('is_active', true)
            ->with(['reads' => function ($q) use ($adminUserId) {
                $q->where('admin_user_id', $adminUserId);
            }])
            ->orderByDesc('id')
            ->paginate(5)
            ->map(function ($item) {
                $item->is_read = $item->reads->isNotEmpty();
                return $item;
            });
    }
}
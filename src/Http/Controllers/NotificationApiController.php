<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Models\Notification;
use Dcat\Admin\Models\NotificationRead;
use Dcat\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationApiController extends Controller
{
    /**
     * 获取当前用户的通知列表（用于下拉）
     */
    public function index()
    {
        $userId = Admin::user()->id ?? 0;
        $notifications = Notification::getAllForUser($userId);

        return response()->json([
            'status' => true,
            'data' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'content' => $n->content,
                    'type' => $n->type,
                    'is_read' => $n->is_read,
                    'created_at' => $n->created_at->format('Y-m-d H:i'),
                ];
            }),
            'unread_count' => Notification::getUnreadCountForUser($userId),
        ]);
    }

    /**
     * 标记单条通知已读
     */
    public function read(Request $request, $id)
    {
        $userId = Admin::user()->id ?? 0;

        NotificationRead::firstOrCreate(
            ['notification_id' => $id, 'admin_user_id' => $userId],
            ['read_at' => now()]
        );

        return response()->json([
            'status' => true,
            'message' => '已标记为已读',
            'unread_count' => Notification::getUnreadCountForUser($userId),
        ]);
    }

    /**
     * 获取第一条未读通知（用于自动弹窗）
     */
    public function firstUnread()
    {
        $userId = Admin::user()->id ?? 0;
        $notification = Notification::getUnreadForUser($userId)->first();

        if (!$notification) {
            return response()->json(['status' => false, 'data' => null]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'content' => $notification->content,
                'type' => $notification->type,
                'created_at' => $notification->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * 全部标记已读
     */
    public function readAll()
    {
        $userId = Admin::user()->id ?? 0;
        $unread = Notification::getUnreadForUser($userId);

        foreach ($unread as $notification) {
            NotificationRead::firstOrCreate(
                ['notification_id' => $notification->id, 'admin_user_id' => $userId],
                ['read_at' => now()]
            );
        }

        return response()->json([
            'status' => true,
            'message' => '全部已读',
        ]);
    }
}
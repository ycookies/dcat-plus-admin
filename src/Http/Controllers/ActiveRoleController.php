<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Authorization\ActiveRole;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActiveRoleController extends Controller
{
    /**
     * Switch the current administrator session to one of its own roles.
     */
    public function switchRole(Request $request, ActiveRole $activeRole)
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'min:1'],
        ]);

        if (! $role = $activeRole->switch(Admin::user(), $data['role_id'])) {
            return response()->json([
                'status'  => false,
                'message' => trans('admin.role_switch_invalid'),
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => trans('admin.role_switch_succeeded'),
            'role' => [
                'id'   => $role->getKey(),
                'name' => $role->name,
            ],
        ]);
    }
}

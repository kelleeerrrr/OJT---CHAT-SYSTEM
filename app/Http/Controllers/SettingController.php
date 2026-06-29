<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function toggleChat(): JsonResponse
    {
        // Only superadmin can toggle chat
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized. Only superadmin can toggle chat.'], 403);
        }

        $isEnabled = Setting::toggleChat();

        return response()->json([
            'chat_enabled' => $isEnabled,
            'message' => $isEnabled ? 'Chat has been enabled.' : 'Chat has been disabled.'
        ]);
    }

    public function getStatus(): JsonResponse
    {
        return response()->json([
            'chat_enabled' => Setting::isChatEnabled()
        ]);
    }
}

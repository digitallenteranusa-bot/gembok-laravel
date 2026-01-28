<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Send WhatsApp message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $result = $this->whatsapp->send($validated['phone'], $validated['message']);

        return response()->json($result);
    }

    /**
     * Check WhatsApp gateway status
     */
    public function status()
    {
        $status = $this->whatsapp->checkStatus();

        if ($status) {
            return response()->json([
                'success' => true,
                'status' => 'connected',
                'data' => $status
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 'disconnected'
        ]);
    }
}

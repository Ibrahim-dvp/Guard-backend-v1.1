<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // Return mock settings that match the frontend expectations
        $settings = [
            'general' => [
                'systemName' => 'Guard CRM',
                'systemVersion' => '1.1.0',
                'maintenanceMode' => false,
                'timezone' => 'UTC',
                'dateFormat' => 'MM/DD/YYYY',
                'language' => 'en',
            ],
            'security' => [
                'sessionTimeout' => 30,
                'passwordPolicy' => [
                    'minLength' => 8,
                    'requireUppercase' => true,
                    'requireLowercase' => true,
                    'requireNumbers' => true,
                    'requireSymbols' => false,
                ],
                'twoFactorAuth' => false,
                'ipWhitelist' => [],
                'maxLoginAttempts' => 5,
            ],
            'notifications' => [
                'email' => [
                    'enabled' => true,
                    'smtpServer' => 'smtp.gmail.com',
                    'smtpPort' => 587,
                    'username' => '',
                    'password' => '',
                    'fromAddress' => 'noreply@guard.com',
                ],
                'sms' => [
                    'enabled' => false,
                    'provider' => 'twilio',
                    'apiKey' => '',
                    'fromNumber' => '',
                ],
                'push' => [
                    'enabled' => true,
                    'fcmServerKey' => '',
                ],
            ],
            'api' => [
                'rateLimit' => 100,
                'timeout' => 30,
                'retryAttempts' => 3,
                'debugMode' => false,
                'logLevel' => 'info',
            ],
            'backup' => [
                'enabled' => true,
                'frequency' => 'daily',
                'retention' => 30,
                'location' => 'cloud',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        // In a real implementation, you would validate and save the settings
        $settings = $request->all();

        return response()->json([
            'success' => true,
            'data' => $settings,
            'message' => 'Settings updated successfully.',
        ]);
    }

    public function reset()
    {
        // Return default settings
        $defaultSettings = [
            'general' => [
                'systemName' => 'Guard CRM',
                'systemVersion' => '1.1.0',
                'maintenanceMode' => false,
                'timezone' => 'UTC',
                'dateFormat' => 'MM/DD/YYYY',
                'language' => 'en',
            ],
            'security' => [
                'sessionTimeout' => 30,
                'passwordPolicy' => [
                    'minLength' => 8,
                    'requireUppercase' => true,
                    'requireLowercase' => true,
                    'requireNumbers' => true,
                    'requireSymbols' => false,
                ],
                'twoFactorAuth' => false,
                'ipWhitelist' => [],
                'maxLoginAttempts' => 5,
            ],
            'notifications' => [
                'email' => [
                    'enabled' => true,
                    'smtpServer' => 'smtp.gmail.com',
                    'smtpPort' => 587,
                    'username' => '',
                    'password' => '',
                    'fromAddress' => 'noreply@guard.com',
                ],
                'sms' => [
                    'enabled' => false,
                    'provider' => 'twilio',
                    'apiKey' => '',
                    'fromNumber' => '',
                ],
                'push' => [
                    'enabled' => true,
                    'fcmServerKey' => '',
                ],
            ],
            'api' => [
                'rateLimit' => 100,
                'timeout' => 30,
                'retryAttempts' => 3,
                'debugMode' => false,
                'logLevel' => 'info',
            ],
            'backup' => [
                'enabled' => true,
                'frequency' => 'daily',
                'retention' => 30,
                'location' => 'cloud',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $defaultSettings,
            'message' => 'Settings reset to defaults.',
        ]);
    }
}

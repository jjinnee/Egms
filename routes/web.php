<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlertSettingsController;
use App\Http\Controllers\BackupRecoveryController;
use App\Http\Controllers\WebPushController;
use App\Http\Controllers\SMSController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ==================== PUBLIC ROUTES ====================
Route::get('/', function () {
    return view('welcome');
});

// Admin login form (public)
Route::get('/admin-login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');

// Handle login (public)
Route::post('/admin-login', [AdminAuthController::class, 'postlogin'])->name('admin.login.submit');

// Logout routes
Route::post('/admin/logout', function(Request $request){
    $request->session()->forget('admin_logged_in');
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('admin.login');
})->name('admin.logout');

Route::post('/logout', function(Request $request){
    $request->session()->forget('admin_logged_in');
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('admin.login');
})->name('logout');


// ==================== PROTECTED ROUTES (Require Login) ====================
Route::middleware(['admin.auth'])->group(function () {
    
    // Dashboard
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboardtest');
    Route::get('/dashboardtest', function() {
        return view('dashboardtest');
    })->name('dashboardtest');

    // Admin API endpoints
    Route::get('/admin/api/stats', [AdminDashboardController::class, 'stats'])->name('admin.api.stats');
    Route::get('/admin/api/logs', [AdminDashboardController::class, 'logs'])->name('admin.api.logs');
    Route::get('/admin/api/device-status', [AdminDashboardController::class, 'deviceStatus'])->name('admin.api.device_status');
    Route::get('/admin/api/devices', [AdminDashboardController::class, 'getDevices'])->name('admin.api.devices');

    // Device Management
    Route::resource('devices', DeviceController::class);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'analytics'])->name('analytics.index');
    Route::get('/analytics/stats', [AnalyticsController::class, 'stats'])->name('analytics.stats');
    Route::get('/analytics/logs', [AnalyticsController::class, 'logs'])->name('analytics.logs');
    Route::get('/analytics/monthly-outages', [AnalyticsController::class, 'getMonthlyOutages'])->name('analytics.monthly-outages');
    Route::get('/analytics/outage-stats', [AnalyticsController::class, 'getOutageStats'])->name('analytics.outage-stats');
    Route::get('/analytics/weekly-devices', [AnalyticsController::class, 'weeklyOutageAnalytics'])->name('analytics.weekly-devices');
    Route::get('/analytics/weekly-outage-view', [AnalyticsController::class, 'getWeeklyOutageView'])->name('analytics.weekly-outage-view');
    Route::get('/analytics/weekly-outage-view-barangay', [AnalyticsController::class, 'getWeeklyOutageViewBarangay'])->name('analytics.weekly-outage-view-barangay');

    // Dashboard Stats
    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats'])->name('dashboard.stats');
    Route::get('/api/power-outages', [DashboardController::class, 'getPowerOutagesData'])->name('api.power-outages');

    // Alert Settings
    Route::get('/settings/alerts', [AlertSettingsController::class, 'index'])->name('settings.alerts');
    Route::post('/settings/alerts/save', [AlertSettingsController::class, 'store'])->name('settings.alerts.save');
    Route::post('/settings/alerts/test', [AlertSettingsController::class, 'testAlert'])->name('settings.alerts.test');

    // Test pages
    Route::get('/test-alerts', function() {
        return view('test-alerts');
    });
    Route::get('/test-push', function() {
        return view('test-push');
    });

    // Alert Logs API
    Route::get('/api/alert-logs', function() {
        $logs = \App\Models\AlertLog::latest()->take(10)->get();
        
        return response()->json([
            'logs' => $logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'device_id' => $log->device_id,
                    'barangay' => $log->barangay,
                    'alert_type' => $log->alert_type,
                    'message' => $log->message,
                    'created_at' => $log->created_at->format('M d, Y H:i:s'),
                    'created_at_raw' => $log->created_at->toISOString()
                ];
            }),
            'count' => $logs->count(),
            'last_updated' => now()->format('g:i:s A')
        ]);
    });

    // Web Push Notification API routes
    Route::prefix('api/webpush')->group(function () {
        Route::get('/vapid-public-key', [WebPushController::class, 'getVapidPublicKey']);
        Route::post('/subscribe', [WebPushController::class, 'subscribe']);
        Route::post('/unsubscribe', [WebPushController::class, 'unsubscribe']);
        Route::post('/resubscribe', [WebPushController::class, 'resubscribe']);
        Route::post('/test', [WebPushController::class, 'testNotification']);
    });

    // SMS API routes
    Route::prefix('api/sms')->group(function () {
        Route::post('/test', [SMSController::class, 'testSMS']);
        Route::post('/outage-alert', [SMSController::class, 'sendOutageAlert']);
        Route::post('/simulate-outage', [SMSController::class, 'simulateOutage']);
        Route::get('/balance', [SMSController::class, 'getBalance']);
    });
});


// ==================== PUBLIC API ROUTES (for Arduino/external devices) ====================
// These need to be accessible without login for IoT devices
Route::post('/api/outages/check', function() {
    return response()->json(['status' => 'checked']);
});

// Public devices API for Arduino (keep this accessible)
Route::get('/api/devices', [AdminDashboardController::class, 'getDevices'])->name('api.devices');

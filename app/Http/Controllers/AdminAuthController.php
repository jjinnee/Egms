<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        // If already logged in, redirect to dashboard
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboardtest');
        }
        return view('admin-login');
    }

    public function postlogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Admin credentials - password is hashed
        $adminEmail = 'admin@soleco.com';
        // Hashed version of 'admin123'
        $adminPasswordHash = '$2y$12$IZQfQhnLrKBeF7XZm8EYf.MF75B3aci6MioOTelWkzORt3jIq1t/y';

        // Verify email and password using Hash::check
        if (
            strtolower(trim($credentials['email'])) === strtolower($adminEmail) &&
            Hash::check(trim($credentials['password']), $adminPasswordHash)
        ) {
            // Regenerate session for security
            $request->session()->regenerate();
            $request->session()->put('admin_logged_in', true);
            $request->session()->put('admin_email', $adminEmail);
            
            return redirect()->intended(route('admin.dashboardtest'));
        }

        return back()->with('error', 'Invalid credentials');
    }
}


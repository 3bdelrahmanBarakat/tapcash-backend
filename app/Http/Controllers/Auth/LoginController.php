<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\ThrottleLogger;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        if (auth()->check()) {
            return response()->json(['message' => 'User is already authenticated.'], 200);
        }

        $this->validate($request, [
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $maxAttempts = 5;
        $decayMinutes = 5;

        $key = $this->getLoginAttemptsKey($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many login attempts. Please try again after '.$seconds.' seconds.',
            ], 429);
        }

        if (!auth()->attempt($request->only('phone_number', 'password'))) {
            RateLimiter::hit($key, $decayMinutes);

            throw ValidationException::withMessages([
                'phone_number' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);

        $user = $request->user();

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ]);
    }

    private function getLoginAttemptsKey(Request $request): string
    {
        $ip = $request->ip();

        return 'login_attempts:'.$ip.':'.$request->input('phone_number');
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'User logged out successfully.'], 200);

    }
}

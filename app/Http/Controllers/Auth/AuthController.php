<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Vonage\Client\Credentials\Basic;
use Vonage\Client;
use Vonage\SMS\Message\SMS;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $request->validate([
        'phone_number' => ['required', 'unique:users'],
        'password' => ['required', 'min:8'],
        'first_name' => ['required'],
        'last_name' => ['required'],
    ]);

    $user = User::create([
        'phone_number' => $request->phone_number,
        'password' => $request->password,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
    ]);

    return response()->json([
        'status' => True,
        'message' => 'User registered successfully.',
        'data' =>[
            'user' => $user,
        ]
    ], 201);
}

public function savePinCode(Request $request)
{
    $request->validate([
        'pin_code' => ['required', 'digits:5'],
        'user_id' => 'required',
    ]);

    $user = User::findOrFail($request->user_id);
    $user->pin_code = bcrypt($request->input('pin_code'));
    $user->save();

    return response()->json([
        'status' => True,
        'message' => 'Pin code saved successfully',
        'data' => null
    ]);
}

public function login(Request $request)
{
    $request->validate([
        'phone_number' => ['required'],
        'password' => ['required'],
        'pin_code' => ['required', 'digits:5'],
    ]);

    $credentials = $request->only('phone_number', 'password');
    if (!Auth::attempt($credentials)) {
        // Increase the login attempts for the user
        RateLimiter::hit($this->throttleKey($request), 1);

        return response()->json([
            'status' => false,
            'message' => 'Invalid phone number or password',
            'data' => null
        ], 401);
    }

    $user = Auth::user();
    if (!password_verify($request->input('pin_code'), $user->pin_code)) {
        // Increase the login attempts for the user
        RateLimiter::hit($this->throttleKey($request), 1);

        return response()->json([
            'status' => false,
            'message' => 'Invalid pin code',
            'data' => null
        ], 401);
    }

    // Reset the login attempts for the user
    RateLimiter::clear($this->throttleKey($request));

    $token = JWTAuth::fromUser($user);

    return response()->json([
        'status' => True,
        'message' => 'User successfully logged in',
        'data' => [
            'token' => $token,
             'user' => $user,
        ]
    ],200);
}

    protected function throttleKey(Request $request)
{
    return Str::lower($request->input('phone_number')) . '|' . $request->ip();
}

public function logout(Request $request)
{
    Auth::logout();

    return response()->json([
        'status' => True,
        'message' => 'User logged out successfully.',
        'data' => null
    ]);
}

public function sendOtp(Request $request)
{
$request->validate([
'phone_number' => ['required'],
]);
$otp = Str::random(6);
    $message = "Your verification code is: {$otp}";

    $basic = new Basic(env('VONAGE_KEY'), env('VONAGE_SECRET'));
    $client = new Client($basic);

    $response = $client->sms()->send(
        new SMS($request->phone_number, env('VONAGE_SMS_FROM'), $message)
    );

    Cache::put($this->otpKey($request), $otp, now()->addMinutes(5));

    return response()->json([
        'message' => 'Verification code sent successfully.',
    ]);
}

public function verifyOtp(Request $request)
{
    $request->validate([
        'phone_number' => ['required'],
        'otp' => ['required'],
    ]);

    $otp = Cache::get($this->otpKey($request));

    if (!$otp || $otp !== $request->otp) {
        throw ValidationException::withMessages([
            'otp' => 'The verification code is invalid or has expired.',
        ]);
    }

    // Reset the OTP for the user
    Cache::forget($this->otpKey($request));
    $user = User::where('phone_number',$request->phone_number)->first();

    // Create or retrieve the user based on the phone number
    $user->mobile_verified_at = Carbon::now();
    $user->mobile_verify_code = $request->otp;
    $user->save();

    $token = JWTAuth::fromUser($user);

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
}

protected function otpKey(Request $request)
{
    return 'otp_' . Str::lower($request->input('phone_number'));
}


}

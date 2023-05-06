<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
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
        'phone_number' => ['required', 'unique:users' , 'min:13', 'max:13'],
        'password' => ['required', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*[0-9])/'],
        'first_name' => ['required','string'],
        'last_name' => ['required','string'],
    ]);

    $user = User::create([
        'phone_number' => $request->phone_number,
        'password' => $request->password,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
    ]);


    $balance = Balance::create([
        'user_id' => $user->id,
    ]);

    return response()->json([
        'status' => True,
        'message' => 'User registered successfully.',
        'data' =>[
            'user' => $user,
            'balance' => $balance,
            'errors' => null
        ],
    ], 201);
}

public function savePinCode(Request $request)
{
    $request->validate([
        'pin_code' => ['required', 'digits:6' ,'numeric'],
        'user_id' => 'required',
    ]);


    $user = User::findOrFail($request->user_id);
    if($user->pin_code)
    {
        return response()->json([
            'status' => false,
            'message' => 'You already set your pin',
            'data' =>[null,
        'errors' => True
        ]
        ]);
    }
    $user->pin_code = bcrypt($request->input('pin_code'));
    $user->save();

    return response()->json([
        'status' => True,
        'message' => 'Pin code saved successfully',
        'data' =>[null,
        'errors' => null
        ]
    ],201);
}

protected function incrementLoginAttempts(Request $request)
    {
        $this->incrementLoginAttempts($request);
    }

public function login(Request $request)
{
    // $request->validate([
    //     'phone_number' => ['required'],
    //     'password' => ['required'],
    //     'pin_code' => ['required', 'digits:5'],
    // ]);

    // $credentials = $request->only('phone_number', 'password');
    // if (!Auth::attempt($credentials)) {
    //     // Increase the login attempts for the user
    //     RateLimiter::hit($this->throttleKey($request), 1);

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'Invalid phone number or password',
    //         'data' => null
    //     ], 401);
    // }

    // $user = Auth::user();
    // if (!password_verify($request->input('pin_code'), $user->pin_code)) {
    //     // Increase the login attempts for the user
    //     RateLimiter::hit($this->throttleKey($request), 1);

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'Invalid pin code',
    //         'data' => null

    //     ], 401);
    // }

    // // Reset the login attempts for the user
    // RateLimiter::clear($this->throttleKey($request));

    // $token = JWTAuth::fromUser($user);

    // return response()->json([
    //     'status' => True,
    //     'message' => 'User successfully logged in',
    //     'data' => [
    //         'token' => $token,
    //          'user' => $user,
    //     ],
    //     'errors'=>null
    // ],200);


    $request->validate([
        'phone_number' => ['required', 'min:13'],
        'password' => ['required', 'min:4'],
    ]);


    $credentials = $request->only('phone_number', 'password');
    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        if (!$user->mobile_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'Mobile number not verified',
                'data' =>[null,
                'errors' => True
            ],
            ], 401);
        }

        if (!$user->pin_code) {
                return response()->json([
                    'status' => false,
                    'message' => 'You should set your pin',
                    'data' =>null,
                    'errors' => True
        ], 401);

        }

        // Reset the login attempts for the user
        RateLimiter::clear($this->throttleKey($request));

        // Update the user's last login timestamp
        $user->last_login_at = now();
        $user->save();

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => True,
            'message' => 'user login successfully',
            'data' => [
                'token' => $token,
                 'user' => $user,
                 'errors' => null,
            ],

        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Invalid phone number or password',
            'data' =>[
                null,
            'errors' => True
            ]

        ], 401);
    }
}

public function loginByPin(Request $request)
{
    $request->validate([
        'pin_code' => ['required'],
    ]);

    $user = Auth::user();

    // Check if the pin code matches the user's pin code
    if (Hash::check($request->pin_code, $user->pin_code)) {
        // Reset the login attempts for the user
        RateLimiter::clear($this->throttleKey($request));

        return response()->json([
            'status' => True,
            'message' => 'User login successfully.',
            'data' => [
                'token' => JWTAuth::fromUser($user),
                'user' => $user,
                'errors' => null
            ],
        ]);
    } else {
        // Increment the login attempts for the user
        RateLimiter::hit($this->throttleKey($request));

        return response()->json([
            'status' => false,
            'message' => 'Invalid pin code.',
            'data' =>[null,
        'errors' => True
        ]
        ], 422);
    }
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
        'data' =>[null,
        'errors' => null
        ]
    ]);
}

public function sendOtp(Request $request)
{
$request->validate([
'phone_number' => ['required',  'min:13', 'max:13'],
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
        'status' => True,
        'message' => "Mobile verified successfully",
        'data' => [
            'token' => $token,
            'user' => $user,
            'errors' => null
        ],
    ]);
}

protected function otpKey(Request $request)
{
    return 'otp_' . Str::lower($request->input('phone_number'));
}


}

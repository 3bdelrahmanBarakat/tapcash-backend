<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeleteAccountController extends Controller
{
    public function deleteAccount()
    {
        User::findOrFail(Auth::user()->id)->delete();
        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }
}

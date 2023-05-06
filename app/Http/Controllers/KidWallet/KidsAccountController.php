<?php

namespace App\Http\Controllers\KidWallet;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class KidsAccountController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'phone_number' => 'required|unique:users',
            'password' => 'required',
        ]);

        $parent = Auth::user();

        $kid = new User([
            'first_name' => $request->first_name,
            'last_name' => $parent->first_name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'type' => "kid",
            'parent_id' => $parent->id,
        ]);

        $kid->save();

        Balance::create([
            'user_id' => $kid->id,
        ]);

        return response()->json([
            'message' => 'Kid account created successfully.',
            'kid' => $kid,
        ]);
    }

    public function enableOrDisable(Request $request)
    {
        $parent = Auth::user();

        $request->validate([
            'kid_id' => 'required'
        ]);

        $kid = User::where('id', $request->kid_id)
        ->where('parent_id',$parent->id)
        ->first();
        if(!$kid)
        {
             return response()->json(['message' => 'Invalid Kid Information.'], 400);
        }
        if($kid->enabled == false)
        {
            $kid->enabled = True;
            $kid->save();

            return response()->json([
                'message' => 'Kid account enabled successfully.',
                'kid' => $kid,
            ]);
        }
        $kid->enabled = false;
            $kid->save();

            return response()->json([
                'message' => 'Kid account disabled successfully.',
                'kid' => $kid,
            ]);

    }

    public function sendMoney(Request $request,)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|gt:40',
            'kid_id' => 'required'
        ]);

        $parent = Auth::user();

        $kid = User::where('id', $request->kid_id)
        ->where('parent_id',$parent->id)
        ->first();

        if (!$kid) {
            return response()->json(['message' => 'Kid wallet not found'], 404);
        }

        if ($kid->enabled == false) {
            return response()->json(['message' => 'Kid wallet is disabled'], 403);
        }



        if ($parent->balance->amount < $request->amount) {
            return response()->json(['message' => 'Not enough balance in parent wallet'], 403);
        }

        $parent->balance->amount -= $request->amount;
        $kid->balance->amount += $request->amount;

        Balance::where('user_id', $parent->id)->update(['amount'=> $parent->balance->amount]);
        Balance::where('user_id', $kid->id)->update(['amount'=> $kid->balance->amount]);
        // $kid->balance->amount->increment('balance', $request->amount);
        // $parent->balance->amount->decrement('balance', $request->amount);

        return response()->json(['message' => 'Money sent successfully']);
    }
}
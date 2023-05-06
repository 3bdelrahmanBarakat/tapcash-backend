<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\SmartCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SmartCardController extends Controller
{

public function generateSmartCard(Request $request)
{
    $user = Auth::user();
    $validity_date = Carbon::now()->addDay();


    $cardNumber = mt_rand(1000000000000000, 9999999999999999);
    $cvv = mt_rand(100, 999);

    $smartCard_exists= SmartCard::where('user_id',$user->id)->first();

    if($smartCard_exists)
    {
        $smartCard_exists->delete();
    }

    $smartCard = new SmartCard([
        'user_id' => $user->id,
        'card_number' =>  $cardNumber,
        'validity_date' => $validity_date,
        'cvv' => $cvv,
        'status' => 'active',
    ]);

    $smartCard->save();

    return response()->json([
        'message' => 'Temporary credit card generated successfully.',
        'card_number' => $cardNumber,
        'validity_date' => $validity_date,
        'card_holder' => $user->first_name." ".$user->last_name,
        'cvv' => $cvv,
    ]);
}

public function processTransaction(Request $request)
{
    $request->validate([
        'card_number' => 'required|digits:16',
        'validity_date' => 'required',
        'cvv' => 'required|digits:3',
        'amount' => 'required|numeric|min:0.01|gt:40',
    ]);

    $smartCard = SmartCard::where('card_number', $request->card_number)
        ->where('validity_date', $request->validity_date)
        ->where('cvv', $request->cvv)
        ->where('status', 'active')
        ->first();

    if (!$smartCard) {
        return response()->json(['message' => 'Invalid card information.'], 400);
    }

    if ($smartCard->validity_date < Carbon::now()) {
        $smartCard->status = 'expired';
        $smartCard->save();
        return response()->json(['message' => 'Temporary credit card has expired.'], 400);
    }

    $user = $smartCard->user;
    $balance = $user->balance->amount;

    if ($balance < $request->amount) {
        return response()->json(['message' => 'Insufficient balance.'], 400);
    }

    $user->balance->amount -= $request->amount;
    Balance::where('user_id', $user->id)->update(['amount'=> $user->balance->amount]);

    $smartCard->status = 'used';
    $smartCard->save();

    return response()->json([
        'message' => 'Transaction successful.',
        'remaining_balance' => $user->balance->amount,
    ]);
}
}
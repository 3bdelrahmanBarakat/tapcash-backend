<?php

namespace App\Http\Controllers\Money;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferMoneyController extends Controller
{
    public function transfer(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|min:13|max:13',
            'amount' => 'required|numeric|min:0.01|gt:40',
        ]);

        $user = Auth::user();
        $balance = $user->balance->amount;

        // Check if the user has enough balance to transfer
        if ($balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        // Check if the recipient phone number exists and verified
        $recipient = User::where('phone_number', $request->phone_number)
            ->whereNotNull('mobile_verified_at')
            ->first();


        if (!$recipient) {
            return response()->json(['message' => 'Invalid recipient phone number.'], 400);
        }

        // Deduct the transferred amount from the user's balance
        $user->balance->amount -= $request->amount;
        Balance::where('user_id', $user->id)->update(['amount'=> $user->balance->amount]);

        // Add the transferred amount to the recipient's balance
        $recipient->balance->amount += $request->amount;
        Balance::where('user_id', $recipient->id)->update(['amount'=> $recipient->balance->amount]);

        return response()->json(['message' => 'Transfer successful.']);
    }
}

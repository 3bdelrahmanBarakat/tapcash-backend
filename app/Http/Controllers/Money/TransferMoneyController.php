<?php

namespace App\Http\Controllers\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\TransferMoneyRequest;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferMoneyController extends Controller
{
    public function transfer(TransferMoneyRequest $request)
    {
        
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

        Transaction::insert([
            [
            'sender_id' => $user->id,
            'receiver_id' => $recipient->id,
            'amount' => $request->amount,
            'type' => 'send'
            ],
        [
            'sender_id' => $user->id,
            'receiver_id' => $recipient->id,
            'amount' => $request->amount,
            'type' => 'receive'
        ]
        ]);

        return response()->json([
            'message' => 'Transfer successful.',
            'balance' => $user->balance->amount
        ]);
    }
}

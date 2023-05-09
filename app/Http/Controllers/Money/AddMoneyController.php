<?php

namespace App\Http\Controllers\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\AddMoneyRequest;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddMoneyController extends Controller
{
    public function addMoney(AddMoneyRequest $request)
    {
        
        $user = Auth::user();

        $user->balance->amount += $request->amount;
        Balance::where('user_id', $user->id)->update(['amount'=> $user->balance->amount]);

        Transaction::create([
            'sender_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'add'
        ]);
        return response()->json([
            'message' => 'Successfully added ' . $request->amount . ' to your balance.',
            'balance' => $user->balance->amount,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Money;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddMoneyController extends Controller
{
    public function addMoney(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|gt:30',
        ]);

        $user = Auth::user();
        $user->balance->amount += $request->amount;
        Balance::where('user_id', $user->id)->update(['amount'=> $user->balance->amount]);
        Transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'add'
        ]);
        return response()->json([
            'message' => 'Successfully added ' . $request->amount . ' to your balance.',
            'balance' => $user->balance->amount,
        ]);
    }
}

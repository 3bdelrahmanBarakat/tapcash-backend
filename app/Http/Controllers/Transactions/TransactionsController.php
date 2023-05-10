<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionsController extends Controller
{
    public function view()
    {

        $user_id = Auth::user()->id;

        $transactions = Transaction::where('sender_id', $user_id)->where('type', 'send')
    ->orWhere(function ($query) use ($user_id) {
        $query->where('receiver_id', $user_id)
            ->where('type', 'receive');
    })
    ->orWhere(function ($query) use ($user_id) {
        $query->where('sender_id', $user_id)
            ->whereIn('type', ['add', 'pay']);
    })
    ->distinct()
    ->get();

        return response()->json([
            'message' => 'User Transactions.',
            'transactions' => $transactions,
        ]);
    }
}

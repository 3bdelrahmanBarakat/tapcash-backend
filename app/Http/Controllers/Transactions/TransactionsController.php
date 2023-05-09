<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionsController extends Controller
{
    public function view()
    {
        $transactions = Auth::user()->transactions;

        return response()->json([
            'message' => 'User Transactions.',
            'balance' => $transactions,
        ]);
    }
}

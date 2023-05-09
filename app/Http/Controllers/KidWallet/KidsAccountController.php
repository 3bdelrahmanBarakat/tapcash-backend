<?php

namespace App\Http\Controllers\KidWallet;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\ForbiddenProduct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class KidsAccountController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'unique:users' , 'min:13', 'max:13'],
            'password' => ['required', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*[0-9])/'],
            'first_name' => ['required','string'],
        ]);

        $parent = Auth::user();

        $kid = new User([
            'first_name' => $request->first_name,
            'last_name' => $parent->first_name,
            'phone_number' => $request->phone_number,
            'password' => $request->password,
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
        
        Transaction::insert([
            [
            'user_id' => $parent->id,
            'amount' => $request->amount,
            'type' => 'send'
            ],
        [
            'user_id' => $kid->id,
            'amount' => $request->amount,
            'type' => 'receive'
        ]
        ]);
        return response()->json(['message' => 'Money sent successfully']);
    }

    public function selectForbiddenProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|unique:forbidden_products,product_id',
            'kid_id' => 'required|integer',
        ]);

        $kid_wallet = User::find($request->kid_id);

        if (!$kid_wallet) {
            return response()->json(['message' => 'Kid wallet not found'], 404);
        }

        if ($kid_wallet->is_disabled) {
            return response()->json(['message' => 'Kid wallet is disabled'], 403);
        }
        //Don't forget to sync
        foreach ($request->product_ids as $product_id) {
            ForbiddenProduct::create([
                'product_id' => $product_id,
                'kid_id' => $kid_wallet->id,
            ]);
        }


        return response()->json(['message' => 'Forbidden products updated successfully']);
    }

    public function deleteForbiddenProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|exists:forbidden_products,product_id',
            'kid_id' => 'required|integer',
        ]);

        $kid_wallet = User::find($request->kid_id);

        if (!$kid_wallet) {
            return response()->json(['message' => 'Kid wallet not found'], 404);
        }

        if ($kid_wallet->is_disabled) {
            return response()->json(['message' => 'Kid wallet is disabled'], 403);
        }

        foreach ($request->product_ids as $product_id) {
            ForbiddenProduct::where('product_id' , $product_id)->where('kid_id' , $kid_wallet->id)->delete();
        }

        return response()->json(['message' => 'Forbidden products deleted successfully']);
    }
}

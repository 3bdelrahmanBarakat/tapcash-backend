<?php

namespace App\Http\Controllers\KidWallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kids\CreateKidAccountRequest;
use App\Http\Requests\Kids\DeleteForbiddenProductsRequest;
use App\Http\Requests\Kids\EnableOrDisableKidAccountRequest;
use App\Http\Requests\Kids\SelectForbiddenProductsRequest;
use App\Http\Requests\Kids\SendKidMoneyRequest;
use App\Models\Balance;
use App\Models\ForbiddenProduct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class KidsAccountController extends Controller
{
    public function create(CreateKidAccountRequest $request)
    {

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

    public function enableOrDisable(EnableOrDisableKidAccountRequest $request)
    {
        $parent = Auth::user();


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

    public function sendMoney(SendKidMoneyRequest $request,)
    {

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
            'sender_id' => $parent->id,
            'receiver' => $kid->id,
            'amount' => $request->amount,
            'type' => 'send'
            ],
        [
            'sender_id' => $parent->id,
            'receiver' => $kid->id,
            'amount' => $request->amount,
            'type' => 'receive'
        ]
        ]);
        return response()->json(['message' => 'Money sent successfully']);
    }

    public function selectForbiddenProducts(SelectForbiddenProductsRequest $request)
    {

        $kid_wallet = User::find($request->kid_id);

        if (!$kid_wallet) {
            return response()->json(['message' => 'Kid wallet not found'], 404);
        }

        if ($kid_wallet->is_disabled) {
            return response()->json(['message' => 'Kid wallet is disabled'], 403);
        }

        foreach ($request->products_category as $product_category) {
            ForbiddenProduct::create([
                'product_category' => $product_category,
                'kid_id' => $kid_wallet->id,
            ]);
        }


        return response()->json(['message' => 'Forbidden products updated successfully']);
    }

    public function deleteForbiddenProducts(DeleteForbiddenProductsRequest $request)
    {

        $kid_wallet = User::find($request->kid_id);

        if (!$kid_wallet) {
            return response()->json(['message' => 'Kid wallet not found'], 404);
        }

        if ($kid_wallet->is_disabled) {
            return response()->json(['message' => 'Kid wallet is disabled'], 403);
        }

        foreach ($request->products_category as $product_category) {
            ForbiddenProduct::where('product_category' , $product_category)->where('kid_id' , $kid_wallet->id)->delete();
        }

        return response()->json(['message' => 'Forbidden products deleted successfully']);
    }

    public function viewKids()
    {
        $kids = User::with('balance')->where('parent_id', Auth::user()->id)->get();
        if(!$kids){
            return response()->json(['message' => 'There are no kids in your account'], 404);
        }
        return response()->json(['kids' => $kids]);
    }
}

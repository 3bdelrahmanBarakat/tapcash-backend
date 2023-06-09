<?php

namespace App\Http\Controllers\Pay;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pay\PayRequest;
use App\Models\Balance;
use App\Models\Bill;
use App\Models\ForbiddenProduct;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PayController extends Controller
{
    public function pay(PayRequest $request)
{

    // Check if the user has sufficient balance
    $user = Auth::user();
    if ($request->type === 'product') {
        $product = Product::find($request->id);

        if(!$product)
        {
            return response()->json(['message' => 'Product not found.'], 400);
        }

        $price = $product->price;
        $name = $product->name;

        $forbiddenProduct = ForbiddenProduct::where('product_category', $product->category)->first();

        if($user->type == "kid" && $forbiddenProduct)
        {
            return response()->json(['error' => 'Sorry,this product is forbidden'], 403);
        }

        if ($user->balance->amount < $price) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        $user->balance->amount -= $price;
        Balance::where('user_id', $user->id)->update(['amount'=> $user->balance->amount]);

        Transaction::create([
            //sender_id is a user_id
            'sender_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'type' => 'pay'
        ]);

        return response()->json([
            'message' => 'Product purchased successfully.',
            'name' => $name,
            'price' => $price,
            'balance' => $user->balance->amount,
        ]);
    }

    // Update the bill status if applicable
    else {
        if($user->type == "kid")
        {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $bill = Bill::where('user_id', $user->id)->where('id', $request->id)->first();

        if(!$bill){
            return response()->json(['message' => 'The bill is not found.'], 400);
        }

        if ($bill->status === 'paid') {
            return response()->json(['message' => 'The bill is already paid.'], 400);
        }

        $price = $bill->price;
        $billNumber = $bill->id;

        if ($user->balance->amount < $price) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        $user->balance->amount -= $price;
        Balance::where('user_id', $user->id)->update(['amount'=> $user->balance->amount]);

        $bill->status = 'paid';
        $bill->save();

        Transaction::create([
            //sender_id is a user_id
            'sender_id' => $user->id,
            'bill_id' => $bill->id,
            'amount' => $bill->price,
            'type' => 'pay'
        ]);

        return response()->json([
            'message' => 'Bill paid successfully.',
            'bill_number' => $billNumber,
            'price' => $price,
            'balance' => $user->balance->amount,
        ]);
    }
}

}

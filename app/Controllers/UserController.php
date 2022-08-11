<?php

namespace App\Controllers;

use Buki\Router\Http\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;

class UserController extends Controller
{
  public function balance(Response $response)
  {
    $userId = auth()->user()->id;
    $user = User::find($userId);

    $total_credit = $user->credit_balance;
    $balance = $total_credit - ($user->cart->total ?? 0);

    return response_json($response, [
      'total_credit' => $total_credit,
      'balance' => $balance
    ]);
  }

  public function whatCanIBuyWithMyCredit(Response $response)
  {
    $userId = auth()->user()->id;
    $user = User::find($userId);

    $total_credit = $user->credit_balance;
    $balance = $total_credit - ($user->cart->total ?? 0);

    $products = Product::where('price', '<=', $balance)->get()->toArray();
    $products = array_map(function ($product) use ($balance) {
      $buyable_quantity = intval($balance / floatval($product['price']));

      return [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'image' => $product['image'],
        'buyable_quantity' => $buyable_quantity,
        'leftover_balance' => $balance - (floatval($product['price']) * $buyable_quantity)
      ];
    }, $products);

    return response_json($response, [
      'total_credit' => $total_credit,
      'balance' => $balance,
      'products' => $products
    ]);
  }

  public function fillMyCartRandomly(Response $response)
  {
    $userId = auth()->user()->id;

    $cart = Cart::where('user_id', $userId)->first(); // check if cart exists
    if (!$cart) {
      // add new cart to database
      $cart = Cart::create([
        'user_id' => $userId,
      ]);
    }

    $user = User::find($userId);

    $total_credit = $user->credit_balance;
    $balance = $total_credit - ($user->cart->total ?? 0);

    while ($balance > 0) {
      $product = Product::where('price', '<=', $balance)->inRandomOrder()->first();
      if (!$product) {
        break;
      }

      $cartItem = CartItem::where('cart_id', $user->cart->id)
        ->where('product_id', $product->id)
        ->first(); // check if cart item exists
      if (!$cartItem) {
        // add new cart item to database
        $cartItem = CartItem::create([
          'cart_id' => $cart->id,
          'product_id' => $product->id,
          'quantity' => 1,
        ]);
      } else {
        // update cart item quantity in database
        $cartItem->quantity = $cartItem->quantity + 1;
        $cartItem->save();
      }

      // update cart total in database
      $cart = Cart::where('user_id', $userId)->first();
      $cart->total = $cart->total + $product->price * 1;
      $cart->save();

      $balance = $balance - $product->price;
    }

    return response_json($response, [
      'success' => 'Cart filled with random products',
      'total_credit' => $total_credit,
      'balance' => $balance,
    ]);
  }
}

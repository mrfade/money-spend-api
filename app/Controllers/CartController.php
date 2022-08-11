<?php

namespace App\Controllers;

use Buki\Router\Http\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

class CartController extends Controller
{
  public function listItems(Response $response)
  {
    $userId = auth()->user()->id;
    $cart = Cart::where('user_id', $userId)->first();

    // create cart if not exists
    if (!$cart) {
      $cart = Cart::create([
        'user_id' => $userId,
      ]);
    }

    $cartItems = CartItem::where('cart_id', $cart->id)->with('product')->get()->toArray();
    $totalPrice = $cart->total;

    return response_json($response, [
      'totalPrice' => $totalPrice,
      'cartItems' => $cartItems
    ]);
  }

  public function addItem(Request $request, Response $response)
  {
    $userId = auth()->user()->id;
    $user = User::find($userId);

    $quantity = intval($request->get('quantity')) ?? 1; // get quantity from request

    $productId = intval($request->get('product_id')); // get product_id from request
    if (!$productId) {
      return response_json($response, ['error' => 'Product ID is required']);
    }

    $product = Product::find($productId); // check if product exists
    if (!$product) {
      return response_json($response, ['error' => 'Product not found']);
    }

    $cart = Cart::where('user_id', $userId)->first(); // check if cart exists
    if (!$cart) {
      // add new cart to database
      $cart = new Cart();
      $cart->user_id = $userId;
      $cart->save();
    }

    // check if user have enough credit balance to buy this product
    if ($user->credit_balance < $cart->total + $product->price * $quantity) {
      $_response = [
        'error' => 'Not enough credit balance',
        'exceeded' => $cart->total + $product->price * $quantity - $user->credit_balance, // credit balance that user need to buy this product with quantity
      ];

      $balance = $user->credit_balance - $cart->total; // user's left balance

      $still_can_buy_quantity_of = intval($balance / $product->price); // get how many products user can buy with his credit balance
      if ($still_can_buy_quantity_of > 0) {
        $_response['still_can_buy_quantity_of'] = $still_can_buy_quantity_of;
      }

      // which product should remove from cart to make user can buy this product with his credit balance
      if ($still_can_buy_quantity_of === 0) {
        $needed_balance = $product->price - $balance; // balance that user need to buy this product with quantity

        // get all product in cart
        $cartItems = CartItem::where('cart_id', $cart->id)->with('product')->get()->toArray();

        $product_should_be_removed = [];
        foreach ($cartItems as $cartItem) {
          $total_price = $cartItem['product']['price'] * $cartItem['quantity']; // total price of this product in cart
          $quantity_to_remove = 0; // quantity of product that should be removed from cart

          // check if when user remove all quantity of this product from cart, can he still buy this product
          if ($total_price > $needed_balance) {
            // now calculate how many quantity of this product should user remove from cart 
            $quantity_to_remove = ceil($needed_balance / $cartItem['product']['price']);
          }

          // if this quantity is greater than 0, then add this product to array
          // this means that user can buy requested product if he remove this product from cart
          if ($quantity_to_remove > 0) {
            $product_should_be_removed[] = [
              'product' => $cartItem['product'],
              'quantity' => $quantity_to_remove,
            ];
          }
        }

        $_response['product_removal_recommendations'] = $product_should_be_removed;
      }

      return response_json($response, $_response);
    }

    $cartId = User::find($userId)->cart->id; // get cart_id from database

    $isCartItemExists = CartItem::where('cart_id', $cartId)
      ->where('product_id', $productId)
      ->first(); // check if cart item exists
    if (!$isCartItemExists) {
      // add new cart item to database
      $cartItem = new CartItem();
      $cartItem->cart_id = $userId;
      $cartItem->product_id = $productId;
      $cartItem->quantity = $quantity;
      $cartItem->save();
    } else {
      // update cart item quantity in database
      $cartItem = CartItem::where('cart_id', $cartId)->where('product_id', $productId)->first();
      $cartItem->quantity = $cartItem->quantity + $quantity;
      $cartItem->save();
    }

    // update cart total in database
    $cart = Cart::where('user_id', $userId)->first();
    $cart->total = $cart->total + $product->price * $quantity;
    $cart->save();

    // decrease balance of user
    // $user = User::find($userId);
    // $user->credit_balance = $user->credit_balance - $product->price;
    // $user->save();

    return response_json($response, [
      'success' => 'Product added to cart',
      'data' => $cartItem,
      'credit_balance' => $user->credit_balance - $cart->total,
    ]);
  }

  public function removeItem(Request $request, Response $response)
  {
    $userId = auth()->user()->id;
    $user = User::find($userId);

    $deleteAll = $request->get('quantity') === '0'; // get quantity from request
    $quantity = intval($request->get('quantity')) ?? 1; // get quantity from request
    // var_dump($quantity);
    // var_dump($deleteAll);
    // exit;

    $productId = $request->get('product_id'); // get product_id from request
    if (!$productId) {
      return response_json($response, ['error' => 'Product ID is required']);
    }

    // check if product exists
    $product = Product::find($productId);
    if (!$product) {
      return response_json($response, ['error' => 'Product not found']);
    }

    // check if cart exists
    $cart = Cart::where('user_id', $userId)->first();
    if (!$cart) {
      return response_json($response, ['error' => 'Cart not found']);
    }

    // check if cart item exists
    $cartItem = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();
    if (!$cartItem) {
      return response_json($response, ['error' => 'Cart item not found']);
    }

    // get cart item quantity
    $_quantity = $cartItem->quantity;

    // prevent user from removing more than existing quantity
    if ($_quantity < $quantity || $deleteAll) {
      $quantity = $_quantity;
    }

    // calculate left quantity
    $_quantity = $_quantity - $quantity;

    // update cart item quantity in database
    if ($_quantity === 0) {
      $cartItem->delete();
    } else {
      $cartItem->quantity = $_quantity;
      $cartItem->save();
    }

    // update cart total in database
    $cart = Cart::where('user_id', $userId)->first();
    $cart->total = $_quantity > 0 ? $cart->total - $product->price * $quantity : 0;
    $cart->save();

    // increase balance of user
    // $user = User::find($userId);
    // $user->credit_balance = $user->credit_balance + $cartItem->product->price;
    // $user->save();

    return response_json($response, [
      'success' => 'Product removed from cart',
      'credit_balance' => $user->credit_balance - $cart->total,
    ]);
  }
}

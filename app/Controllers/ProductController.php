<?php

namespace App\Controllers;

use Buki\Router\Http\Controller;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Product;

class ProductController extends Controller
{
  public function index(Response $response)
  {
    $products = Product::all()->toArray();

    return response_json($response, $products);
  }

  public function test()
  {
    return 'test';
  }
}

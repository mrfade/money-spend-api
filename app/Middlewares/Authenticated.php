<?php

namespace App\Middlewares;

use Symfony\Component\HttpFoundation\Response;

class Authenticated
{
  public function handle(Response $response)
  {
    if (!auth()->isLoggedIn()) {
      response_json($response, ['error' => 'Unauthorized'], 401);
      return false;
    }

    return true;
  }
}

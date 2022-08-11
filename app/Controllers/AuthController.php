<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;

class AuthController
{
  public function login(Request $request, Response $response)
  {
    $username = $request->get('username');
    $password = $request->get('password');

    $user = User::where('username', $username)->first();
    if (!$user) {
      return response_json($response, ['error' => 'User not found'], 404);
    }

    if (!$user->verifyPassword($password)) {
      return response_json($response, ['error' => 'Invalid password'], 401);
    }

    $token = auth()->issueToken($user);

    return response_json($response, [
      'token' => $token->toString(),
      'expires_at' => $token->claims()->get('exp'),
    ]);
  }

  public function register(Request $request, Response $response)
  {
    $username = $request->get('username');
    $password = $request->get('password');

    $user = User::where('username', $username)->first();
    if ($user) {
      return response_json($response, ['error' => 'User already exists'], 409);
    }

    $user = User::create([
      'username' => $username,
      'password' => password_hash($password, PASSWORD_DEFAULT),
      'credit_balance' => 100000000,
    ]);

    $token = auth()->issueToken($user);

    return response_json($response, [
      'token' => $token->toString(),
      'expires_at' => $token->claims()->get('exp'),
    ]);
  }
}

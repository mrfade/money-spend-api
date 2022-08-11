<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
  protected $fillable = ['username', 'password', 'credit_balance'];

  public function cart()
  {
    return $this->hasOne(Cart::class);
  }

  public function verifyPassword(string $password): bool
  {
    return password_verify($password, $this->password);
  }
}

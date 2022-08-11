<?php

namespace Core;

use Symfony\Component\HttpFoundation\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Exception as JWTException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Arrilot\DotEnv\DotEnv;

use App\Models\User;

class Auth
{

  private static ?Auth $instance = null;
  private User $user;
  private Plain $token;
  private array $claims;

  private InMemory $key;

  public static function getInstance(): Auth
  {
    if (!isset(self::$instance)) {
      self::$instance = new Auth();
    }

    return self::$instance;
  }

  private function __construct()
  {
    $request = Request::createFromGlobals();
    $token = $request->headers->get('authorization');

    $this->key = InMemory::base64encoded(DotEnv::get('JWT_KEY'));

    if (!$token) {
      return;
    }

    // Bearer xxx
    $_token = explode(' ', $token)[1];

    // check if token is valid
    if (!$this->isTokenValid($_token)) {
      return;
    }

    // parse token
    $this->claims = $this->parseToken($_token);

    // print_r($this->claims);
    // exit;

    $userId = intval($this->claims['id']);

    $this->user = User::find($userId) ?? null;
  }

  public function isLoggedIn(): bool
  {
    return isset($this->user);
  }

  public function user(): User
  {
    return $this->user;
  }

  public function issueToken(User $user): \Lcobucci\JWT\Token\Plain
  {
    $config = Configuration::forSymmetricSigner(new Sha256(), $this->key);
    $now = new \DateTimeImmutable();
    $token = $config->builder()
      ->issuedBy('http://example.com')
      ->permittedFor('http://example.org')
      ->identifiedBy(bin2hex(random_bytes(10)))
      ->issuedAt($now)
      ->expiresAt($now->modify('+1 hour'))
      ->withClaim('id', $user->id)
      ->withClaim('username', $user->username)
      ->getToken($config->signer(), $config->signingKey());

    return $token;
  }

  private function parseToken(string $token): array
  {
    $config = Configuration::forSymmetricSigner(new Sha256(), $this->key);
    $this->token = $config->parser()->parse($token);
    return $this->token->claims()->all();
  }

  private function isTokenValid(string $token): bool
  {
    try {
      $config = Configuration::forSymmetricSigner(new Sha256(), $this->key);

      $parse = $config->parser()->parse($token);

      $clock = SystemClock::fromUTC();
      $constraints = [
        new LooseValidAt($clock),
      ];

      if (!$config->validator()->validate($parse, ...$constraints)) {
        throw new \Exception('No way!');
      }
    } catch (\Exception $e) {
      return false;
    }

    return true;
  }
}

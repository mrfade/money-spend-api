<?php

namespace Core;

use Buki\Router\Router;
use Illuminate\Database\Capsule\Manager as Capsule;
use Arrilot\DotEnv\DotEnv;

class Bootstrap
{

  public $router;

  public function __construct()
  {

    DotEnv::load(dirname(__DIR__) . '/.env.php');

    $this->router = new Router([
      'paths' => [
        'controllers' => 'app/Controllers',
        'middlewares' => 'app/Middlewares',
      ],
      'namespaces' => [
        'controllers' => 'App\Controllers',
        'middlewares' => 'App\Middlewares',
      ],
      'debug' => true
    ]);

    $capsule = new Capsule;

    $capsule->addConnection([
      'driver'    => 'mysql',
      'host'      => DotEnv::get('DB_HOST', 'localhost'),
      'database'  => DotEnv::get('DB_NAME'),
      'username'  => DotEnv::get('DB_USER'),
      'password'  => DotEnv::get('DB_PASSWORD'),
      'charset'   => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci'
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
  }

  public function run()
  {
    $this->router->run();
  }
}

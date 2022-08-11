<?php

use Symfony\Component\HttpFoundation\Response;
use Core\Auth;

function auth(): Auth
{
  return Auth::getInstance();
}

function response_json(Response $response, array | string $data, $statusCode = 200)
{
  $response->setStatusCode($statusCode);
  $response->headers->set('Content-type', 'application/json');
  $response->setContent(json_encode($data));
  $response->send();
}

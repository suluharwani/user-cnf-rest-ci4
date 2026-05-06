<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getServer('HTTP_AUTHORIZATION');
        
        if (!$authHeader) {
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON(['error' => 'No token provided']);
            return $response;
        }
        
        try {
            $token = str_replace('Bearer ', '', $authHeader);
            $key = getenv('jwt.secret_key');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            // Save user data in request
            $request->user_id = $decoded->user_id;
            $request->username = $decoded->username;
            
        } catch (ExpiredException $e) {
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON(['error' => 'Token expired']);
            return $response;
        } catch (\Exception $e) {
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON(['error' => 'Invalid token']);
            return $response;
        }
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
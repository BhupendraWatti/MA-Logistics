<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiBasicAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user_id')) {
            return null;
        }

        [$username, $password] = $this->credentials($request);
        if ($username === '' || $password === '') {
            return $this->unauthorized('Basic authentication credentials are required.');
        }

        $user = (new UserModel())->attemptLogin([
            'username' => $username,
            'password' => $password,
        ]);
        if (!$user || !(int) ($user['is_active'] ?? 0)) {
            return $this->unauthorized('Invalid username or password.');
        }

        $this->establishSession($user);
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'private, no-store');
    }

    public static function establishSession(array $user): void
    {
        session()->set([
            'user_id'  => (int) $user['id'],
            'username' => (string) $user['username'],
            'role'     => (string) ($user['role'] ?? 'user'),
            'branch_id'=> (int) ($user['branch_id'] ?? 1),
            'permissions' => [
                'can_create' => (int) ($user['can_create'] ?? 0),
                'can_edit'   => (int) ($user['can_edit'] ?? 0),
                'can_delete' => (int) ($user['can_delete'] ?? 0),
            ],
        ]);
    }

    private function credentials(RequestInterface $request): array
    {
        $header = trim((string) ($request->getHeaderLine('Authorization')
            ?: $request->getServer('HTTP_AUTHORIZATION')
            ?: $request->getServer('REDIRECT_HTTP_AUTHORIZATION')
            ?: ''));
        if (stripos($header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($header, 6), true);
            if (is_string($decoded) && str_contains($decoded, ':')) {
                return array_map('trim', explode(':', $decoded, 2));
            }
        }

        return [
            trim((string) ($request->getServer('PHP_AUTH_USER') ?? '')),
            (string) ($request->getServer('PHP_AUTH_PW') ?? ''),
        ];
    }

    private function unauthorized(string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setHeader('WWW-Authenticate', 'Basic realm="MAlogistic API"')
            ->setJSON(['status' => 'error', 'message' => $message]);
    }
}

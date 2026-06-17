<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Response\ApiResponse;
use App\Domain\Auth\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LoginAction
{
    public function __construct(
        private AuthService $authService,
        private ApiResponse $response,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $result = $this->authService->login(
            $data['email'] ?? '',
            $data['password'] ?? ''
        );

        return $this->response->json($response, [
            'data' => $result,
        ]);
    }
}

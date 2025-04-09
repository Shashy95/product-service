<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Signer\Key\InMemory;

class CheckAudience
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'No token provided.'], 401);
        }

        try {
            // Parse the token
            $parser = new Parser(new \Lcobucci\JWT\Encoding\JoseEncoder());
            $jwt = $parser->parse($token);

            // Load the public key
            $publicKey = InMemory::file(storage_path('oauth-public.key'));

            // Create validator and validation constraints
            $validator = new Validator();

            // Debugging: Ensure ENV variables are loaded correctly
            Log::info('ENV Variables', [
                'AUTH_DOMAIN' => env('AUTH_DOMAIN'),
                'PRODUCT_CLIENT_ID' => env('PRODUCT_CLIENT_ID'),
            ]);

            // Validate JWT constraints
            $validator->validate($jwt,
                new IssuedBy(env('AUTH_DOMAIN')),
                new PermittedFor(env('PRODUCT_CLIENT_ID'))
            );

            // Validate 'aud' claim
            $allowedAudiences = [env('PRODUCT_CLIENT_ID')];
            $tokenAudience = $jwt->claims()->get('aud');

            // Debugging: Log the audience for inspection
            Log::info('Audience Debug', [
                'allowedAudiences' => $allowedAudiences,
                'tokenAudience' => $tokenAudience,
            ]);

            // Handle 'aud' as array or string
            $isValidAudience = false;
            if (is_array($tokenAudience)) {
                $isValidAudience = array_intersect($allowedAudiences, $tokenAudience);
            } else {
                $isValidAudience = in_array($tokenAudience, $allowedAudiences);
            }

            if (!$isValidAudience) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Unauthorized access. Please re-authenticate.',
                    'redirect' => 'http://auth-server.test/api/login',
                ], 403);
            }

        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('JWT Validation Failed', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token. Please re-authenticate.',
            ], 401);
        }

        return $next($request);
    }
}

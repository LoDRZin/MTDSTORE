<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revogado'
        ]);
    }

    // --- Google OAuth ---

    public function googleRedirect()
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha ao autenticar com o Google'], 401);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Se já existe, apenas vincula o google_id e atualiza avatar se nulo
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $user->avatar ?? $googleUser->getAvatar(),
            ]);
        } else {
            // Cria um novo usuário
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null, // Sem senha
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Redireciona para o frontend com o token na URL (Hash ou Param)
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $userData = base64_encode(json_encode($user));
        
        return redirect()->to("{$frontendUrl}/auth/callback?token={$token}&user={$userData}");
    }

    // --- Cart Persistence ---

    public function cartSync(Request $request)
    {
        $request->validate([
            'cart_data' => 'array',
        ]);

        $user = $request->user();
        $user->cart_data = $request->cart_data;
        $user->save();

        return response()->json(['message' => 'Carrinho salvo com sucesso']);
    }

    public function cartLoad(Request $request)
    {
        return response()->json([
            'cart_data' => $request->user()->cart_data ?? []
        ]);
    }}

<?php

namespace App\Http\Controllers;

use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Resend\Laravel\Facades\Resend;

class AuthController extends Controller
{
    public function sendCode(Request $request): JsonResponse
    {
        $this->purgeInactiveUsers();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        $user = User::firstOrCreate(
            ['email' => $email],
        );

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        LoginCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Resend::emails()->send([
            'from' => 'SALN2 <noreply@upcsweb.dev>',
            'to' => [$email],
            'subject' => 'SALN Portal Login Code',
            'html' => "<p>Your SALN login verification code is {$code}. This code expires in 10 minutes.</p>"
        ]);

        return response()->json([
            'email' => $email,
            'status' => 'A 6-digit verification code was sent to your email.',
            'otp_sent' => true
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'regex:/^[0-9]{6}$/'],
            'email' => ['required', 'email', 'max:255'],
            'otp_sent' => ['required', 'boolean:strict', 'accepted'],
        ]);

        $email = $validated['email'];

        $user = User::firstWhere('email', $email);
        $userId = $user->id;
        
        $loginCode = LoginCode::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $loginCode || ! Hash::check($validated['code'], $loginCode->code_hash)) {
            return response()->json([
                'message' => 'Invalid or expired code. Please request a new one.'
            ], 401);
        }

        $loginCode->used_at = now();
        $loginCode->save();

        $user->forceFill(['last_active_at' => now()])->save();

        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL()*60,
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['status' => 'Successfully logged out']);
    }

    private function purgeInactiveUsers(): void
    {
        $cutoff = now()->subDays(5);

        User::query()
            ->where(function ($query) use ($cutoff): void {
                $query->whereNotNull('last_active_at')
                    ->where('last_active_at', '<', $cutoff);
            })
            ->orWhere(function ($query) use ($cutoff): void {
                $query->whereNull('last_active_at')
                    ->where('created_at', '<', $cutoff);
            })
            ->delete();
    }
}

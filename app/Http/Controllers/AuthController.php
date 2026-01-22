<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login', [
            "title" => env("APP_NAME")
        ]);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower($data['email']);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $email,
                'password' => Str::random(64),
            ]
        );

        $now = Carbon::now();

        $rateLimitSeconds = 30;
        if ($user->last_otp) {
            $secondsSinceLast = max(0, $now->getTimestamp() - $user->last_otp->getTimestamp());
            if ($secondsSinceLast < $rateLimitSeconds) {
                $wait = $rateLimitSeconds - $secondsSinceLast;
                return back()->withErrors([
                    'email' => "Please wait {$wait} seconds before requesting another link.",
                ]);
            }
        }

        $otpHash = $this->generateOtpHash();

        $user->fill([
            'otp_hash' => $otpHash,
            'otp_consumed' => false,
            'last_otp' => $now,
        ])->save();

        $loginUrl = $this->buildLoginUrl($user->email, $otpHash, $now);

        Mail::raw(
            "Use this link to complete your login:\n{$loginUrl}\nIf you do not recognize this request, ignore it.\n",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your one-time login link');
            }
        );

        return back()->with(
            'status',
            'If the email address is valid we sent a login link. Check your inbox (and junk/spam folders).'
        );
    }

    public function loginWithToken(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');
        $lastOtp = $request->query('last_otp');

        if (! $email || ! $token || ! $lastOtp) {
            return response('Token is invalid.', Response::HTTP_BAD_REQUEST);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response('Token is invalid.', Response::HTTP_BAD_REQUEST);
        }

        if ($user->otp_consumed) {
            return response('Token was already consumed.', Response::HTTP_CONFLICT);
        }

        if ($user->otp_hash !== $token || ! $this->lastOtpMatches($user, $lastOtp)) {
            return response('Token is invalid.', Response::HTTP_BAD_REQUEST);
        }

        $user->update(['otp_consumed' => true]);

        return response()->json($user);
    }

    public function anonymousLogin(Request $request)
    {
        $macAddress = $this->resolveMacAddress($request);

        $user = User::where('mac_address', $macAddress)->first();

        if (! $user) {
            $user = User::create([
                'name' => 'anonymous-' . Str::random(6),
                'email' => 'anonymous+' . Str::uuid() . '@example.test',
                'password' => Str::random(64),
                'mac_address' => $macAddress,
            ]);
        }

        $user->fill([
            'mac_address' => $macAddress,
            'otp_hash' => $this->generateOtpHash(),
            'otp_consumed' => false,
            'last_otp' => Carbon::now(),
        ])->save();

        return response()->json($user);
    }

    private function resolveMacAddress(Request $request): string
    {
        $source = $request->query('mac_address')
            ?? $request->header('X-Mac-Address')
            ?? $request->header('x-mac-address')
            ?? $request->ip()
            ?? 'unknown-mac';

        return strtoupper((string) $source);
    }

    private function lastOtpMatches(User $user, string $lastOtp): bool
    {
        if (! $user->last_otp) {
            return false;
        }

        return $user->last_otp->format('Y-m-d H:i:s') === $lastOtp;
    }

    private function buildLoginUrl(string $email, string $token, Carbon $timestamp): string
    {
        $query = http_build_query([
            'email' => $email,
            'token' => $token,
            'last_otp' => $timestamp->format('Y-m-d H:i:s'),
        ]);

        return url('/otp/login') . '?' . $query;
    }

    private function generateOtpHash(): string
    {
        return md5(Str::uuid()->toString() . microtime(true));
    }
}

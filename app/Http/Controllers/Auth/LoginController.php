<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auditor;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.required' => 'Informe o e-mail corporativo.',
            'email.email' => 'E-mail inválido.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
        ]);

        $key = Str::transliterate(Str::lower($data['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$seconds}s.",
            ]);
        }

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($key, 120);
            try {
                Auditor::log('Tentativa de login inválida', 'segurança', ['email' => $data['email']]);
            } catch (\Throwable) {
            }

            return back()->withErrors([
                'email' => 'E-mail ou senha incorretos. Verifique suas credenciais.',
            ])->onlyInput('email');
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            Auditor::log('Login bloqueado (usuário inativo)', 'segurança', ['email' => $data['email']]);

            return back()->withErrors(['email' => 'Este acesso está bloqueado. Contate o administrador master.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        Auditor::log('Login realizado', 'segurança');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auditor::log('Logout', 'segurança');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sessão encerrada com segurança.');
    }

    public function forgot()
    {
        return view('auth.forgot');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink($request->only('email'));
            Auditor::log('Solicitação de redefinição de senha', 'segurança', ['email' => $request->email]);
        } catch (\Throwable) {
            // Mantém a mesma resposta para não revelar falha de e-mail/cadastro.
        }

        return back()->with('ok', 'Se o e-mail estiver cadastrado, enviaremos as instruções de redefinição.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Link inválido ou expirado. Solicite um novo.']);
        }

        Auditor::log('Senha redefinida', 'segurança', ['email' => $request->email]);

        return redirect()->route('login')->with('ok', 'Senha atualizada. Faça login para continuar.');
    }
}

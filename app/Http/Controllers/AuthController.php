<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Показать форму входа
     */
    public function showLoginForm()
    {
        return view('login');
    }

    /**
     * Обработать запрос на вход
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');
        
        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            $user->last_activity = now();
            $user->save();
            
            $request->session()->regenerate();
            
            // Логируем успешный вход
            Log::info('Пользователь вошел в систему', ['user_id' => $user->id, 'email' => $user->email]);
            
            // Перенаправление на главную страницу с Vue SPA
            return redirect('/');
        }

        return back()->withErrors([
            'email' => 'Неверные учетные данные.',
        ])->withInput();
    }

    /**
     * Показать форму регистрации
     */
    public function showRegistrationForm()
    {
        return view('register');
    }

    /**
     * Обработать запрос на регистрацию
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'last_activity' => now()
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // Логируем успешную регистрацию
        Log::info('Пользователь зарегистрирован', ['user_id' => $user->id, 'email' => $user->email]);

        // Перенаправление на главную страницу с Vue SPA
        return redirect('/');
    }

    /**
     * Выход пользователя
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
    
    /**
     * Получить статус аутентификации и данные пользователя
     */
    public function getAuthStatus()
    {
        // Логируем запрос статуса аутентификации
        Log::info('Запрос статуса аутентификации', ['authenticated' => Auth::check()]);
        
        if (Auth::check()) {
            $user = Auth::user();
            
            $response = [
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_blocked' => $user->is_blocked,
                    'initials' => strtoupper(substr($user->name, 0, 2)),
                    'registrationDate' => $user->created_at ? $user->created_at->format('d.m.Y') : date('d.m.Y'),
                    'stats' => [
                        'topics' => $user->topics()->count(),
                        'replies' => $user->replies()->count()
                    ]
                ]
            ];
            
            // Логируем ответ
            Log::info('Ответ статуса аутентификации', ['response' => $response]);
            
            return response()->json($response);
        }
        
        return response()->json([
            'authenticated' => false
        ]);
    }
}

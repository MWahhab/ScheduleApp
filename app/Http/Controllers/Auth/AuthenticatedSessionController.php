<?php

namespace App\Http\Controllers\Auth;

use App\{
    Http\Controllers\Controller,
    Http\Requests\Auth\LoginRequest,
    Providers\RouteServiceProvider,
    Utils\Helper
};
use Illuminate\{
    Http\RedirectResponse,
    Http\Request,
    Support\Facades\Auth,
    Validation\ValidationException,
    View\View
};

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  LoginRequest        $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Helper::validateUserDetails($request);

        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

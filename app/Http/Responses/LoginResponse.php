<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $intendedUrl = $request->input('intended_url');

        if ($intendedUrl && $this->isSafeUrl($intendedUrl)) {
            $request->session()->forget('url.intended');

            return redirect($intendedUrl);
        }

        return redirect()->intended('/dashboard');
    }

    // Prevent open-redirect: only allow relative paths, reject protocol-relative URLs (//evil.com)
    protected function isSafeUrl(string $url): bool
    {
        return str_starts_with($url, '/') && ! str_starts_with($url, '//');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(string $locale, Request $request): RedirectResponse
    {
        abort_unless(in_array($locale, config('app.supported_locales', ['fr', 'en']), true), 404);

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function index(Request $request)
    {
        return view('website.index');
    }

    public function privacyPolicy(Request $request)
    {
        return view('website.privacy-policy');
    }

    public function termsOfUse(Request $request)
    {
        return view('website.terms-of-use');
    }

    public function deleteAccount(Request $request)
    {
        return view('website.delete-account');
    }

    /************************** END **************************/ 
}
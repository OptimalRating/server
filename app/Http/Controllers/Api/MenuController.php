<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    public function index()
    {
        //check the user role
        if(Auth::user()->hasRole('super_admin')){
            return config('menu.super_admin');
        }
        elseif(Auth::user()->hasRole('country_admin')){
            return config('menu.country_admin');
        }
    }
}

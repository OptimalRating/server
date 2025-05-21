<?php

namespace App\Http\Controllers\Api;

use App\Category;
use App\CustomObjects\CustomJsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /*
     * dashboard data
     */
    public function index()
    {
        //onay bekleyen kategoriler
        $categories = Category::with(['parent','user.userDetails'])
            ->where('status','pending');

        if(auth()->user()->hasRole('country_admin')){
            $categories  = $categories->where('country_id', '=',auth()->user()->country_id);
        } else {
            $categories  = $categories->where('is_world', true);
        }

        $categories = $categories->orderBy('id','desc')
            ->limit(10)->get();

        $multipleData = ['categories'=> $categories, 'comments'=> null];
        $customResponse = new CustomJsonResponse(200, 'All dashboard data listed', $multipleData,null);
        return $customResponse->getResponse();
    }
}

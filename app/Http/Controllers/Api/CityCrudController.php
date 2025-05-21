<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\CustomObjects\ApiPagination;
use App\Service\CustomJsonResponse;
use App\Validator\CityValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Input;
// use Illuminate\Support\Facades\Request;

class CityCrudController extends Controller
{
    protected $jsonResponse;

    public function __construct(CustomJsonResponse $jsonResponse)
    {
        $this->authorizeResource(City::class, 'city');
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {
        //get the related cities for country admin's country
        $model = City::where('country_id', Auth::user()->country_id)->get();

        $pagination = new ApiPagination(request("limit", 20), count($model), request("offset", 0));

        $this->jsonResponse->setData(
            200,
            'msg.info.list.cities', $model, null, $pagination
        );

        return $this->jsonResponse->getResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $validator = new CityValidator();

        if($response = $validator->validate())
           return $response;

        $store = City::create([
                    'name' => $request->json('name'),
                    'country_id' => Auth::user()->country_id ?? $request->json('country_id') ,
                    'order' => $request->json('order')
                 ]);

        if($store){
            $this->jsonResponse->setData(200,  'msg.info.city.created', $store);
            return $this->jsonResponse->getResponse();
        }

        return $this->cityError();

    }

    /**
     * Display the specified resource.
     *
     * @param City $city
     * @return array
     */
    public function show(City $city)
    {
        $this->jsonResponse->setData(200,   'msg.info.success.city.show', $city);
        return $this->jsonResponse->getResponse();

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return array
     */
    public function update(Request $request, City $city)
    {
        //update the city name if needed
        $city->name = $request->json('name');
        $city->order = $request->json('order');
        $updated = $city->update();

        if($updated){
            $this->jsonResponse->setData(200,  'msg.city.updated', $city);
            return $this->jsonResponse->getResponse();
        }

        $this->cityError();
    }

    /**
     * @param City $city
     * @return array
     * @throws \Exception
     */
    public function destroy(City $city)
    {
        //delete the city if needed
        $deleted = $city->delete();

        if($deleted){
            $this->jsonResponse->setData(200,  'msg.city.deleted', $city);
            return $this->jsonResponse->getResponse();
        }

        $this->cityError();
    }

    /**
     * @return array
     */
    private function cityError()
    {
        $this->jsonResponse->setData(200, 'msg.error.occured:', []);
        return $this->jsonResponse->getResponse();
    }

    /**
     * @param $id
     * @return array
     */
    public function getCitiesWithCountryId($id, Request $request)
    {
        // $getQuery = Input::all();
        $getQuery = $request->all();
        $model = City::where('country_id', $id)
            ->orderBy($getQuery["sort"],$getQuery["order"])
            ->take($getQuery["limit"])
            ->offset($getQuery["offset"])
            ->get();

        $pagination = (new ApiPagination($getQuery["limit"], count(City::where('country_id', $id)->get()), $getQuery["offset"]))->getConvertObject();
        $this->jsonResponse->setData(200, 'msg.info.list.cities', $model, null, $pagination);
        return $this->jsonResponse->getResponse();
    }
}

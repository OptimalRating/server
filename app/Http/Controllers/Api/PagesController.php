<?php

namespace App\Http\Controllers\Api;

use App\CustomObjects\ApiPagination;
use App\PageTranslations;
use App\Service\CustomJsonResponse;
use App\Validator\PageValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Page;
use Illuminate\Http\Response;

class PagesController extends Controller
{
    /**
     * @var CustomJsonResponse
     */
    private $jsonResponse;

    public function __construct(CustomJsonResponse $jsonResponse)
    {
        $this->jsonResponse = $jsonResponse;
    }
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {
        $model = Page::with('translation')->get();

        $pagination = new ApiPagination(request("limit", 10), count($model), request("offset", 0));

        return $this->jsonResponse
            ->setData(
                200,
                'msg.info.list.pages',
                $model, null,
                $pagination->getConvertObject()
            )
            ->getResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return array|bool
     */
    public function store(Request $request)
    {
        if($response = (new PageValidator)->validate())
            return $response;

        $model = Page::create($request->all());

        return $this->jsonResponse
            ->setData(
                200,
                'msg.info.create.pages',
                $model,
                null,
                null
            )
            ->getResponse();
    }

    /**
     * Display the specified resource.
     *
     * @param Page $page
     * @return array
     */
    public function show($id)
    {

        $page = is_string($id) ? Page::where('slug', '=', $id)->first() : Page::where('id','=',$id) ;

        return $this->jsonResponse
            ->setData(
                200,
                'msg.info.show.pages',
                $page, null,
                null
            )
            ->getResponse();

    }


    /**
     * Display the specified resource.
     *
     * @param Page $page
     * @return array
     */
    public function translation($id)
    {
        $page = PageTranslations::where('page_id','=',$id)->first();

        $country = auth()->user()->country()->first();

        if (is_null($page)) {
            $page = new PageTranslations();
        }

        $page->title =\request('title');
        $page->body  =\request('body');
        $page->country_code = $country->code;
        $page->page_id = $id;

        $page->save();

        return $this->jsonResponse
            ->setData(
                200,
                'msg.info.translation.pages',
                $page, null,
                null
            )
            ->getResponse();
    }



    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $page
     * @return array|bool
     */
    public function update(Request $request, Page $page)
    {
        if($response = (new PageValidator)->validate())
            return $response;

        $updated =$page->update($request->all());

        return $this->jsonResponse
            ->setData(
                200,
                'msg.info.update.pages',
                $updated,
                null,
                null
            )
            ->getResponse();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Page $page
     * @return array
     * @throws \Exception
     */
    public function destroy(Page $page)
    {
        if($page->delete()){

            return $this->jsonResponse
                ->setData(
                    200,
                    'msg.info.deleted.pages',
                    $page,
                    null,
                    null
                )
                ->getResponse();
        }
    }

    /**
     * publish the page
     */
    public function publish(Page $page)
    {
        $page->status = 1;

        if($page->update()){
            $this->jsonResponse->setData(200, 'success', 'msg.page.published', $page);
            return $this->jsonResponse->getResponse();
        }
    }
}

<?php


namespace App\Service;


use Illuminate\Http\Request;
use Auth;

class FileService
{
    /**
     * Display a listing of the resource.
     *
     * @param $file
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function upload($file, $type)
    {
        $cdnPath = $this->generateFolder($type);
       
        if(!is_dir(public_path($cdnPath))){
            mkdir(public_path($cdnPath), 0777,true);
        }

        $result = [];
        $reqImage = is_array($file ) ? $file : [$file];
        foreach ($reqImage as $uploadedFile) {

            try {
                $imageName = md5(date_timestamp_get(new \DateTime('now'))).'.'.$reqImage[0]->getClientOriginalExtension();

                $uploadedFile->move(public_path($cdnPath), $imageName);

            }catch (\Exception $e) {
                throw $e;
            }

            $result[] = [
                'user_id' => Auth::user()->id,
                'name' => $imageName,
                'cdnPath' => '/'. $cdnPath .'/'
            ];
        }

        return $result;
    }


    public function updateProductGallery(Request $request, $id)
    {
        $requestedData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $requestedData = $requestedData["data"];

        $product = Products::find($id);

        $product_image = public_path()."/cdn/images/product/";

        if(!$product){
            return $this->ApiResponse([],[],'error',404, 'Not Found!');
        }

        $newGallery = [];
        $title = $product->title;
        foreach ($requestedData["gallery"] as $gallery){
            if(file_exists($product_image.$gallery)){
                $newGallery[] = [
                    "title"=>$title,
                    "image"=>$gallery
                ];
            }
        }

        if($requestedData['cover']){
            $product->cover = ["title"=>$title, "image"=>$requestedData["cover"]];
        }

        $product->gallery = $newGallery;

        $product->save();

        return $this->ApiResponse($product,[],200, 'Galeri Yüklendi');
    }

    public function deleteImage($type, $name)
    {
        $cdnPath = $this->generateFolder($type);
        if (file_exists($cdnPath .'/'. $name)) {
            unlink($cdnPath .'/'. $name);
        }
    }

    private function generateFolder($type)
    {
        $typeList = [
            "ca" => "category",
            "ct" => "country",
            "sl" => "banner",
            "us" => "user",
            "up" => "user_portrait",
            "un" => "user_nationality",
            "sa" => "survey_approval",
            "pr" => "survey",
            "choice" => "choice"
        ];

        if (!array_key_exists($type, $typeList)) {
            return 'cdn/images/survey';
        }

        return 'cdn/images/'.$typeList[$type];
    }

}

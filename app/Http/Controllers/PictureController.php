<?php

namespace App\Http\Controllers;

use App\Models\Picture;
use App\Models\PictureUser;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Monolog\Handler\PushoverHandler;

class PictureController extends Controller
{
    private $pictures;

    private function appendToList($baseName, $path)
    {

        foreach ($this->pictures as $picture) {
            // $picture['url'] = $path;
            if ($picture->file_name == $baseName) {
                $picture['url'] = $path;
            }
        }
    }

    public function index()
    {

        $this->pictures = PictureUser::all();

        // $images = File::files('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        $images = File::files(storage_path('app/public/uploads'));
        $imageUrls = [];

        foreach ($images as $image) {
            // pushing to imageUrls array
            $baseName = basename($image);
            $path = asset('storage/images/' . $baseName);
            array_push($imageUrls, $path);



            $this->appendToList($baseName, $path);
        }

        // $imageUrls = array_map(function ($image) {
        //     // $baseName = basename($image);
        //     // $fileUrl = asset('storage/images/' . $baseName);
        //     // $this->appendToList($baseName, $fileUrl);
        //     // return $fileUrl;

        //     return asset('storage/images/' . basename($image));
        // }, $images);


        // $this->appendToList();
        // return $imageUrls;
        // return $this->pictures;
        // for ($i = 0; $i < count($imageUrls); $i++) {
        // }


        return response()->json(['success' => true, 'msg' => 'Picture successfully fetched', 'pictures' => $this->pictures], 200);
    }

    public function demo(Request $request)
    {
        // return $request->all();
        return $request->hasFile('image');
    }

    // title,uesr_id, pictures[], 
    public function store(Request $request)

    {
        // $fileName = time() . '_' . $request->title . '.' . $request->file('image')->getClientOriginalExtension();

        if (!$request->hasFile('image')) {
            return response()->json(['success' => false, 'msg' => 'No pictures were selected'], 422);
        }


        $fields = $request->validate([
            'title' => 'required',
        ]);

        try {

            DB::beginTransaction();

            $picture = $request->user()->pictures()->create([
                'title' => $request->title,
                'user_id' => $request->user()->id
            ]);

            if (!$picture) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'Error inserting picture in picture table'], 500);
            }
            $fileName = time() . '_' . $request->title . '.' . $request->file('image')->getClientOriginalExtension();

            $picture_user_table = $request->user()->pictureList()->create([
                'user_id' => $request->user()->id,
                'picture_id' => $picture->id,
                'file_name' => $fileName
            ]);

            if (!$picture_user_table) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'Error inserting picture in picture table'], 500);
            }

            if ($request->file('image')->storeAs('public/uploads/', $fileName)) {
                DB::commit();
                return response()->json(['success' => true, 'msg' => 'Picture successfully uploaded'], 200);
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {

            // delete file

            DB::rollBack();
            return response()->json(['success' => false, 'msg' => $e->__tostring()], 500);
        }
    }
}

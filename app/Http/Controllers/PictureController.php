<?php

namespace App\Http\Controllers;

use App\Models\Picture;
use App\Models\PictureList;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\PushoverHandler;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isEmpty;

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

        $this->pictures = PictureList::orderBy('created_at', 'desc')->with('pictureMain')->get();
        // return $this->pictures;
        // $images = File::files('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        $images = File::files(storage_path('app/public/uploads'));
        $imageUrls = [];

        foreach ($images as $image) {
            // pushing to imageUrls array
            $baseName = basename($image);
            $path = asset('storage/uploads/' . $baseName);
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


        // return gettype($this->pictures->toArray());
        return response()->json(['status' => true, 'message' => 'Picture successfully fetched', 'pictures' => $this->pictures], 200);
    }

    public function demo(Request $request)
    {
        // return $request->all();
        return $request->hasFile('image');
    }

    // title,uesr_id, pictures[], 
    public function store(Request $request)

    {

        if (!$request->hasFile('image')) {
            return response()->json(['status' => false, 'message' => 'No pictures were selected'], 422);
        }


        try {
            $fields = $request->validate([
                'title' => 'required',
                'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }



        try {

            DB::beginTransaction();

            $picture = $request->user()->pictures()->create([
                'title' => $request->title,
                'user_id' => $request->user()->id
            ]);

            if (!$picture) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Error inserting picture in picture table'], 500);
            }
            $fileName = time() . '_' . $request->title . '.' . $request->file('image')->getClientOriginalExtension();

            $picture_user_table = $request->user()->pictureList()->create([
                'user_id' => $request->user()->id,
                'picture_id' => $picture->id,
                'file_name' => $fileName
            ]);

            if (!$picture_user_table) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Error inserting picture in picture table'], 500);
            }

            if ($request->file('image')->storeAs('public/uploads/', $fileName)) {
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Picture successfully uploaded'], 200);
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {

            // delete file

            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->__tostring()], 500);
        }
    }


    public function storeMultiple(Request $request)

    {

        if (!$request->hasFile('images')) {
            return response()->json(['status' => false, 'message' => 'No pictures were selected'], 422);
        }

        // validation
        try {
            $fields = $request->validate([
                'title' => 'required',
                'images' => 'array',
                'images*' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }


        // insertion begins
        try {

            DB::beginTransaction();
            // save to pictures table
            $picture = $request->user()->pictures()->create([
                'title' => $request->title,
                'user_id' => $request->user()->id
            ]);

            // create in main pictures table
            if (!$picture) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Error inserting picture in picture table'], 500);
            }

            foreach ($request->images as $image) {
                $fileName = time() . '_' . Str::random(5) . '__' . $request->title . '.' . $image->getClientOriginalExtension();

                $save_to_picture_list_table = $picture->pictureList()->create([
                    'picture_id' => $picture->id,
                    'file_name' => $fileName,
                    'user_id' => $request->user()->id
                ]);

                if (!$save_to_picture_list_table) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Error inserting picture in picture table'], 500);
                }

                $image->storeAs('public/uploads', $fileName);
            }
            DB::commit();
        } catch (\Exception $e) {

            // delete file

            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->__tostring()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        // fetch picture through User model
        $picture =  $request->user()->pictureList()->where('id', $id)->first();
        if (!$picture) {
            return response()->json(['status' => false, 'message' => 'Picture not found!'], 404);
        }

        // return $picture;
        // delete recrod from pictures table
        try {
            DB::beginTransaction();

            $delete = $picture->delete();
            if ($delete < 1) {
                return response()->json(['status' => false, 'message' => 'Error deleting picture. Please try again!'], 500);
            }

            $picture_id = $picture->picture_id;
            $otherPictures = $request->user()->pictureList()->where('picture_id', $picture_id)->get();


            if ($otherPictures->count() < 1) {
                $request->user()->pictures->where('id', $picture_id)->first()->delete();
            }

            if (Storage::exists('public/uploads/' . $picture->file_name)) {
                Storage::delete('public/uploads/' . $picture->file_name);
            }
            // delete record from pictures table 


            DB::commit();
            return response()->json(['status' => true, 'message' => 'Picture deleted fetched', 'pictures' => $this->pictures], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error deleting picture!', 'Exception' => $e->__toString()], 500);
        }



        // delete all records from picture_user table or let cascade on delete do its thing 

        // if delete successful delete frile from directory else rollback
    }
}

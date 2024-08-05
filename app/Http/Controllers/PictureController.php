<?php

namespace App\Http\Controllers;

use App\Models\Picture;
use App\Models\PictureUser;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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

        $this->pictures = PictureUser::orderBy('created_at', 'desc')->with('picture')->get();

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

        // return $request->all();


        if (!$request->hasFile('image')) {
            return response()->json(['success' => false, 'msg' => 'No pictures were selected'], 422);
        }


        try {
            $fields = $request->validate([
                'title' => 'required',
                'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 422);
        }



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


    public function storeMultiple(Request $request)

    {


        if (!$request->hasFile('images')) {
            return response()->json(['success' => false, 'msg' => 'No pictures were selected'], 422);
        }
        // return $request->all();


        try {
            $fields = $request->validate([
                'title' => 'required',
                'images' => 'array',
                'images*' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 422);
        }



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


            foreach ($request->images as $image) {
                $fileName = time() . '_' . $request->title . '.' . $image->getClientOriginalExtension();

                $picture_user_table = $request->user()->pictureList()->create([
                    'user_id' => $request->user()->id,
                    'picture_id' => $picture->id,
                    'file_name' => $fileName
                ]);

                if (!$picture_user_table) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'msg' => 'Error inserting picture in picture table'], 500);
                }

                $image->storeAs('public/uploads', $fileName);
            }
            DB::commit();
        } catch (\Exception $e) {

            // delete file

            DB::rollBack();
            return response()->json(['success' => false, 'msg' => $e->__tostring()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {

        $pictures = $request->user()->pictureList()->where('picture_id', $id)->get();

        // delete recrod from pictures table
        try {
            DB::beginTransaction();

            $pictures = $request->user()->pictureList()->where('picture_id', $id)->get();   // all pictures from pivot table

            $picture_id = $request->user()->pictureList()->where('picture_id', $id)->first()->picture_id;
            // $delete = Picture::destroy($id);    // delete main record and as well as all records on picture_user table

            foreach ($pictures as $picture) {
                // deleting each record at once and removing files as well
                if (!$picture->delete()) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'msg' => 'Error deleting all selected pictures!'], 500);
                }

                if (Storage::exists('public/uploads/' . $picture->file_name)) {
                    Storage::delete('public/uploads/' . $picture->file_name);
                }
            }


            // if count is less than 0, delete
            // $count = PictureUser::count()->where('');


            $delete = Picture::destroy($picture_id);
            if (!$delete) {
                return response()->json(['success' => false, 'msg' => 'Something went wrong. Please try again!'], 500);
            }

            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Picture successfully fetched', 'pictures' => $this->pictures], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'msg' => 'Error deleting picture!'], 500);
        }



        // delete all records from picture_user table or let cascade on delete do its thing 

        // if delete successful delete frile from directory else rollback
    }
}

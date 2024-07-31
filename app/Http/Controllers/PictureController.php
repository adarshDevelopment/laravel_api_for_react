<?php

namespace App\Http\Controllers;

use App\Models\Picture;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PictureController extends Controller
{
    public function index()
    {
        DB::table('picture_user')->get();
    }

    public function demo(Request $request)
    {
        return $request->all();
    }

    // title,uesr_id, pictures[], 
    public function store(Request $request)

    {
        return $request->all();

        dd($request->hasFile('image'));

        $fileName = time() . '_' . $request->title . '_' . $request->file('image')->getClientOriginalExtension();

        dd($request->hasFile('image'));

        return $request->all();

        // dump($request->all());
        // return 'returning from store function pictureController';
        dd($_POST);
        dd($request->hasFile('image'));

        if (!$request->hasFile('images')) {
            return response()->json(['success' => false, 'msg' => 'No pictures were selected'], 422);
        }



        $fields = $request->validate([
            'title' => 'required',
        ]);

        // db transaction begin

        // Picture::create([
        //     'title' => $request->title,
        //     'user_id' => $request->user()->id
        // ]);
        try {

            DB::beginTransaction();

            $picture = $request->user()->picture()->create([
                'title' => $request->title,
                'user_id' => $request->user()->id
            ]);

            if (!$picture) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'Error inserting picture in picture table'], 500);
            }
            $fileName = time() . '_' . $request->title . '_' . $request->file('image')->getClientOriginalExtension();

            $picture_user_table = $request->user()->pictureList()->create([
                'user_id' => $request->user()->id,
                'picture_id' => $picture->id,
                'fileName' => $fileName
            ]);

            if (!$picture_user_table) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'Error inserting picture in picture table'], 500);
            }

            if ($request->file('image')->storeAs('public/uploads/', $fileName)) {
                DB::commit();
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

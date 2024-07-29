<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }

    public function index()
    {
        $posts = Post::all();
        return $posts;
        if (!$posts) {
            return response()->json();
        }
    }


    public function store(Request $request)
    {
        // return $request->all();
        $fields =  $request->validate([
            'title' => 'required|max:225',
            'body' => 'required|'
        ]);

        // $post = Post::create($fields);

        // creating post thorugh logged in authenticated user object 
        $post = $request->user()->posts()->create($fields);

        return ['posts' => $post];
    }


    public function show(Post $post)
    {
        // route model binding 
        return  $post;
    }


    public function update(Request $request, Post $post)
    {
        // Gate::authorize('modify', [$request->user(), $post]);

        Gate::authorize('modify', $post);    // can only pass $post because laravel automatically passes user
        $fields = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required'
        ]);

        $post->update($fields);
        return $post;
    }


    public function destroy(Post $post)
    {
        // return auth()->user();
        Gate::authorize('modify', $post);

        $post->delete();

        return ['message' => 'The post was deleted'];
    }
}

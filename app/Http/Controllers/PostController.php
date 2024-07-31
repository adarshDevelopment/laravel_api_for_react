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

        // Eager loading
        $posts = Post::with('user')->latest()->get();
        if (!$posts) {
            return response()->json(['msg' => 'no posts found']);
        }
        return response()->json(['posts' => $posts, 'success' => true, 'msg' => 'Posts fetched successfully.'], 200);
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

        if (!$post) {
            return response()->json(['success' => false, 'msg' => 'Could not create post'], 500);
        }
        return response()->json(['post' => $post, 'user' => $post->user, 'success' => true, 'msg' => 'Post created successfully.'], 200);
        // return ['posts' => $post];
    }


    public function show(Post $post)
    {
        // route model binding 
        if (!$post) {
            return response()->json(['success' => false, 'msg' => 'Post not found'], 404);
        }
        return response()->json(['post' => $post, 'user' => $post->user, 'success' => true, 'msg' => 'Post fetched successfully.'], 200);
    }


    public function update(Request $request, Post $post)
    {
        // Gate::authorize('modify', [$request->user(), $post]);

        Gate::authorize('modify', $post);    // can only pass $post because laravel automatically passes user
        $fields = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required'
        ]);

        if (!$post->update($fields)) {
            return response()->json(['success' => false, 'msg' => 'Error updating post!'], 500);
        }
        return response()->json(['post' => $post, 'user' => $post->user, 'success' => true, 'msg' => 'Post updated successfully.'], 200);
    }


    public function destroy(Post $post)
    {
        // return auth()->user();
        Gate::authorize('modify', $post);

        if (!$post->delete()) {
            return response()->json(['success' => false, 'msg' => 'Error deleting post!'], 500);
        }

        return response()->json(['post' => $post, 'user' => $post->user, 'success' => true, 'msg' => 'Post deleted successfully.'], 200);
    }
}

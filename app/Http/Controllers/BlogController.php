<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'title' => 'Blog',
            'heading' => 'Blog',
            'posts' => Post::published()->with('category')->latest('published_at')->paginate(12),
            'categories' => BlogCategory::has('posts')->orderBy('name')->get(),
        ]);
    }

    public function category(BlogCategory $blogCategory): View
    {
        return view('blog.index', [
            'title' => $blogCategory->seo_title ?: $blogCategory->name,
            'description' => $blogCategory->seo_description,
            'heading' => $blogCategory->name,
            'posts' => $blogCategory->posts()->published()->with('category')->latest('published_at')->paginate(12),
            'categories' => BlogCategory::has('posts')->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Post $post): View
    {
        abort_unless(
            ($post->status !== 'draft' && $post->published_at?->lte(now())) || $request->user()?->isStaff(),
            404
        );

        $seen = $request->session()->get('viewed_posts', []);
        if (! in_array($post->id, $seen, true)) {
            $post->increment('view_count');
            $request->session()->put('viewed_posts', array_slice([...$seen, $post->id], -200));
        }

        return view('blog.show', [
            'post' => $post->load(['category', 'author:id,name']),
            'more' => Post::published()->whereKeyNot($post->id)->latest('published_at')->take(3)->get(['title', 'slug', 'image', 'published_at']),
        ]);
    }
}

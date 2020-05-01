<?php

namespace App\Http\Controllers\Articles;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfUnconfirmed;
use App\Http\Requests\ArticleRequest;
use App\Jobs\CreateArticle;
use App\Jobs\DeleteArticle;
use App\Jobs\UpdateArticle;
use App\Models\Article;
use App\Models\Tag;
use App\Policies\ArticlePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArticlesController extends Controller
{
    public function __construct()
    {
        $this->middleware([Authenticate::class, RedirectIfUnconfirmed::class], ['except' => ['index', 'show']]);
    }

    public function index()
    {
        return view('articles.index');
    }

    public function show(Article $article)
    {
        abort_unless(
            $article->isPublished() || (Auth::check() && $article->isAuthoredBy(Auth::user())),
            404
        );

        return view('articles.show', [
            'article' => $article,
        ]);
    }

    public function create(Request $request)
    {
        return view('articles.create', [
            'tags' => Tag::all(),
            'selectedTags' => old('tags', []),
            'series' => $request->user()->series,
            'selectedSeries' => old('series'),
        ]);
    }

    public function store(ArticleRequest $request)
    {
        $article = $this->dispatchNow(CreateArticle::fromRequest($request));

        $this->success('articles.created');

        return redirect()->route('articles.show', $article->slug());
    }

    public function edit(Request $request, Article $article)
    {
        $this->authorize(ArticlePolicy::UPDATE, $article);

        return view('articles.edit', [
            'article' => $article,
            'tags' => Tag::all(),
            'selectedTags' => old('tags', $article->tags()->pluck('id')->toArray()),
            'series' => $request->user()->series,
            'selectedSeries' => old('series', $article->series_id),
        ]);
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $this->authorize(ArticlePolicy::UPDATE, $article);

        $article = $this->dispatchNow(UpdateArticle::fromRequest($article, $request));

        $this->success('articles.updated');

        return redirect()->route('articles.show', $article->slug());
    }

    public function delete(Article $article)
    {
        $this->authorize(ArticlePolicy::DELETE, $article);

        $this->dispatchNow(new DeleteArticle($article));

        $this->success('articles.deleted');

        return redirect()->route('articles');
    }
}

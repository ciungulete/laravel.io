<?php

namespace App\Jobs;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\Series;

final class UpdateArticle
{
    private $article;

    private $title;

    private $body;

    private $tags;

    private $series;

    public function __construct(Article $article, string $title, string $body, array $tags = [], string $series = null)
    {
        $this->article = $article;
        $this->title = $title;
        $this->body = $body;
        $this->tags = $tags;
        $this->series = $series;
    }

    public static function fromRequest(Article $article, ArticleRequest $request): self
    {
        return new static(
            $article,
            $request->title(),
            $request->body(),
            $request->tags(),
            $request->series()
        );
    }

    public function handle(): Article
    {
        $this->article->update([
            'title' => $this->title,
            'body' => $this->body,
            'slug' => $this->title,
        ]);
        $this->article->syncTags($this->tags);
        $this->article->updateSeries(Series::find($this->series));
        $this->article->save();

        return $this->article;
    }
}

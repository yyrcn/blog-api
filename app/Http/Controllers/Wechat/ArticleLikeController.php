<?php

namespace App\Http\Controllers\Wechat;

use App\Models\Article;
use App\Models\Like;
use App\Transformers\ArticleTransformer;
use Cache;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ArticleLikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    public function store(Article $article)
    {
        $userId = $this->user->id;
        $lockName = self::class."@store:$userId";

        abort_if(!Cache::lock($lockName, 60)->get(), 422, '操作过于频繁，请稍后再试！');
        // abort_if($article->author_id == $userId,  422, '不能给自己的文章点赞！');


        $like = $article->likes()->withTrashed()->where('user_id', $userId)->first();

        $liked = true;

        if ($like) {
            if ($like->deleted_at) {
                $like->restore();
            } else {
                $like->delete();
                $liked = false;
            }
        } else {
            $like = new Like;
            $like->user_id = $userId;
            $article->likes()->save($like);
        }

        $likeCount = $article->like_count;

        if (!$article->isDirty('like_count')) {
            $likeCount = $liked ? $likeCount + 1 : $likeCount - 1;
        }

        $data = [
            'id' => $article->id,
            'like_count' => $likeCount,
            'likes' => $liked ? [$like] : null,
        ];

        Cache::lock($lockName)->release();

        return compact('data');
    }
}
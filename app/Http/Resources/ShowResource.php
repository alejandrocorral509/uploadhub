<?php

namespace App\Http\Resources;

use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Show */
class ShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_url' => $this->coverUrl(),
            'language' => $this->language,
            'explicit' => $this->explicit,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'author' => new UserResource($this->whenLoaded('user')),
            'episodes_count' => $this->whenCounted('episodes'),
            'subscribers_count' => $this->whenCounted('subscribers'),
            'url' => route('shows.show', $this->resource),
            'feed_url' => $this->feedUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

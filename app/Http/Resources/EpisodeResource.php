<?php

namespace App\Http\Resources;

use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Audio */
class EpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasFile = (bool) $this->filepath;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'privacy' => $this->privacy,
            'season' => $this->season,
            'episode_number' => $this->episode_number,
            'duration' => $this->duration,
            'cover_url' => $this->coverUrl(),
            'type' => $this->isYoutube() ? 'youtube' : ($this->isVideo() ? 'video' : 'audio'),
            'youtube_url' => $this->when($this->isYoutube(), $this->youtube_url),
            'stream_url' => $this->when($hasFile, fn () => route('audios.stream', $this->resource)),
            'download_url' => $this->when($hasFile, fn () => route('audios.download', $this->resource)),
            'processing_status' => $this->processing_status,
            'waveform' => $this->waveform,
            'summary' => $this->summary,
            'chapters' => $this->chapters,
            'transcript' => $this->when($request->boolean('transcripcion'), $this->transcript),
            'plays_count' => $this->plays_count,
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
            'author' => new UserResource($this->whenLoaded('user')),
            'show' => new ShowResource($this->whenLoaded('show')),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->pluck('name')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'url' => $this->url(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $show->title }}</title>
        <link>{{ route('shows.show', $show) }}</link>
        <atom:link href="{{ $show->feedUrl() }}" rel="self" type="application/rss+xml"/>
        <language>{{ $show->language }}</language>
        <description>{{ $show->description ?: $show->title }}</description>
        <itunes:summary>{{ $show->description ?: $show->title }}</itunes:summary>
        <itunes:author>{{ $show->user->name }}</itunes:author>
        <itunes:owner>
            <itunes:name>{{ $show->user->name }}</itunes:name>
        </itunes:owner>
        <itunes:explicit>{{ $show->explicit ? 'true' : 'false' }}</itunes:explicit>
        <itunes:type>episodic</itunes:type>
@if ($show->coverUrl())
        <itunes:image href="{{ $show->coverUrl() }}"/>
        <image>
            <url>{{ $show->coverUrl() }}</url>
            <title>{{ $show->title }}</title>
            <link>{{ route('shows.show', $show) }}</link>
        </image>
@endif
@if ($show->category)
        <itunes:category text="{{ $show->category->name }}"/>
@endif
        <generator>UploadHub</generator>
        <lastBuildDate>{{ ($episodes->first()?->created_at ?? $show->updated_at)->toRfc2822String() }}</lastBuildDate>
@foreach ($episodes as $episode)
        <item>
            <title>{{ $episode->title }}</title>
            <guid isPermaLink="false">uploadhub-episode-{{ $episode->id }}</guid>
            <link>{{ $episode->url() }}</link>
            <pubDate>{{ $episode->created_at->toRfc2822String() }}</pubDate>
            <description>{{ $episode->description ?: $episode->title }}</description>
            <content:encoded><![CDATA[{!! nl2br(e($episode->description ?: $episode->title)) !!}]]></content:encoded>
            <enclosure url="{{ route('audios.stream', $episode) }}" length="{{ $episode->file_size }}" type="{{ $episode->filetype ?: 'audio/mpeg' }}"/>
@if ($episode->duration)
            <itunes:duration>{{ $episode->duration }}</itunes:duration>
@endif
@if ($episode->season)
            <itunes:season>{{ $episode->season }}</itunes:season>
@endif
@if ($episode->episode_number)
            <itunes:episode>{{ $episode->episode_number }}</itunes:episode>
@endif
@if ($episode->cover)
            <itunes:image href="{{ $episode->coverUrl() }}"/>
@endif
            <itunes:explicit>{{ $show->explicit ? 'true' : 'false' }}</itunes:explicit>
        </item>
@endforeach
    </channel>
</rss>

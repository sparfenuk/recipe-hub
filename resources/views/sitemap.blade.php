{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/') }}" />
        <xhtml:link rel="alternate" hreflang="uk" href="{{ url('/') }}" />
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />
    </url>

    <url>
        <loc>{{ route('recipes.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
        <xhtml:link rel="alternate" hreflang="en" href="{{ route('recipes.index') }}" />
        <xhtml:link rel="alternate" hreflang="uk" href="{{ route('recipes.index') }}" />
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ route('recipes.index') }}" />
    </url>

@foreach ($recipes as $recipe)
    <url>
        <loc>{{ route('recipes.show', $recipe->slug) }}</loc>
        @if ($recipe->updated_at)
        <lastmod>{{ $recipe->updated_at->toW3cString() }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        <xhtml:link rel="alternate" hreflang="en" href="{{ route('recipes.show', $recipe->slug) }}" />
        <xhtml:link rel="alternate" hreflang="uk" href="{{ route('recipes.show', $recipe->slug) }}" />
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ route('recipes.show', $recipe->slug) }}" />
    </url>
@endforeach

</urlset>

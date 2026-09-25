{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
@foreach (['it', 'en'] as $locale)
    <url>
        <loc>{{ $page[$locale] }}</loc>
        <xhtml:link rel="alternate" hreflang="it" href="{{ $page['it'] }}" />
        <xhtml:link rel="alternate" hreflang="en" href="{{ $page['en'] }}" />
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $page['it'] }}" />
    </url>
@endforeach
@endforeach
</urlset>

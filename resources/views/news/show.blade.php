@extends('layouts.app')
@section('title', $post->title.' — Комплекс Греция')
@section('seo_title', $post->title.' — '.$site['site_name'])
@section('seo_description', $post->excerpt ?: Str::limit(strip_tags($post->body), 160))
@if($post->image_path) @section('seo_image', $post->image_path) @endif
@section('content')
<article class="article-page"><div class="container article-container"><a href="{{ route('news.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Все новости</a><div class="eyebrow eyebrow-blue mt-4">{{ optional($post->published_at)->translatedFormat('d F Y') }}</div><h1>{{ $post->title }}</h1>@if($post->excerpt)<p class="article-lead">{{ $post->excerpt }}</p>@endif @if($post->image_path)<img class="article-cover" src="{{ Storage::url($post->image_path) }}" alt="{{ $post->title }}">@endif<div class="article-body">{!! nl2br(e($post->body)) !!}</div></div></article>
@endsection

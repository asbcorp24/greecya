@extends('layouts.app')
@section('title', $post->title.' — Комплекс Греция')
@section('content')
<article class="article-page"><div class="container article-container"><a href="{{ route('news.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Все новости</a><div class="eyebrow eyebrow-blue mt-4">{{ optional($post->published_at)->translatedFormat('d F Y') }}</div><h1>{{ $post->title }}</h1>@if ($post->excerpt)<p class="article-lead">{{ $post->excerpt }}</p>@endif @if ($post->image_path)<img class="article-cover" src="{{ Storage::url($post->image_path) }}" alt="{{ $post->title }}">@endif<div class="article-body">{!! nl2br(e($post->body)) !!}</div></div></article>
@endsection

<!doctype html>
<html lang="id">
<head>@include('partials.head', ['title' => $title ?? ''])</head>
<body>
<main id="konten">@yield('konten')</main>
@include('partials.global')
<script src="{{ asset('assets/js/app.js') }}"></script>
@foreach ($scripts ?? [] as $s)<script src="{{ asset($s) }}"></script>@endforeach
</body>
</html>

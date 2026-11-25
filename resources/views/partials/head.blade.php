<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ ($title ?? '') !== '' ? $title . ' | ZIQO' : 'ZIQO: Zone of Interactive Quiz & Opportunity' }}</title>
<meta name="description" content="ZIQO adalah platform kuis edukasi interaktif untuk belajar lintas mata pelajaran dan jenjang.">
<link rel="icon" type="image/webp" href="{{ asset('assets/maskot.webp') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Titan+One&display=swap">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

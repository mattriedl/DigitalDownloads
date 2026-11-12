{{-- No database, settings or Vite here: this page must render even when those are what failed. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $heading }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; box-sizing: border-box;
               background: #0e1520; color: #e6edf5; font: 16px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 34rem; background: #152030; border: 1px solid #22324a; border-radius: 10px; padding: 32px; }
        h1 { margin: 0 0 8px; font-size: 1.9rem; line-height: 1.2; }
        p { margin: 0; color: #9fb0c5; }
        a { display: inline-block; margin-top: 20px; background: #22c55e; color: #052e14; font-weight: 700;
            text-decoration: none; padding: 10px 18px; border-radius: 8px; }
        a:focus-visible { outline: 3px solid #e6edf5; outline-offset: 2px; }
    </style>
</head>
<body>
<main>
    <h1>{{ $heading }}</h1>
    <p>{{ $text }}</p>
    <a href="/">Try the homepage</a>
</main>
</body>
</html>

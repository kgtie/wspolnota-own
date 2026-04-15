<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $theme['service_name'] }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3efe6;">
    @include('mail.framework.card', ['theme' => $theme, 'preheader' => $preheader, 'body_html' => $body_html])
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ "Verification de mail" }}</title>
    <style>
        /* Ajoutez ici votre style personnalisé */
        body {
            font-family: Arial, sans-serif;
            color: #333333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            height: auto;
        }
        .content {
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            color: #888888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img class="logo" src="{{ asset('images/logo.jpeg') }}" alt="Logo">
        </div>
        <div class="content">
            <h1>{{ $data['title'] }}</h1>

            <p>{!! $data['content'] !!}</p>

            <p>Cordialement,<br>{{ "L'Équipe de l'ANPE CENTRE NORD !" }}</p>
        </div>
        <div class="footer">
            © {{ date('Y') }} ANPE CENTRE NORD. Tous droits réservés.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Wspólnota - nowa wiadomość</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* RESET STYLÓW */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            background-color: #F3F4F6;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
            display: block;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        /* GŁÓWNE STYLE */
        .body-font {
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
            color: #333333;
        }

        /* PRZYCISK (Bulletproof button) */
        .btn {
            background-color: #4F46E5;
            /* Twój fiolet */
            background-image: linear-gradient(90deg, #4F46E5 0%, #7c3aed 100%);
            color: #ffffff;
            display: inline-block;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            mso-padding-alt: 0;
            /* Dla Outlooka */
        }

        /* RESPANSYWNOŚĆ */
        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                padding: 0 !important;
            }

            .content-padding {
                padding: 20px !important;
            }

            .header-text {
                font-size: 24px !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #F3F4F6;">

    <div style="display: none; max-height: 0px; overflow: hidden;">
        Wspólnota - Usługa wspierająca Twoją parafię w erze cyfrowej.
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
        style="background-color: #F3F4F6;">
        <tr>
            <td align="center" style="padding: 40px 10px;">

                <table role="presentation" class="container" border="0" cellpadding="0" cellspacing="0" width="600"
                    style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">

                    <tr>
                        <td align="center"
                            style="padding: 30px 0; background-color: #ffffff; border-bottom: 1px solid #f0f0f0;">
                            <a href="https://wspolnota.app" style="text-decoration: none;">
                                <span
                                    style="font-family: 'Poppins', Helvetica, Arial, sans-serif; font-size: 24px; font-weight: bold; color: #4F46E5;">
                                    ⛪ Wspólnota
                                </span>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="height: 6px; background: linear-gradient(90deg, #4F46E5 0%, #F59E0B 100%);"></td>
                    </tr>

                    <tr>
                        <td class="content-padding" style="padding: 40px;">

                            {{ $slot }}

                            <div style="height: 1px; background-color: #E5E7EB; margin: 30px 0;"></div>

                            <p class="body-font"
                                style="margin: 0; font-size: 14px; color: #6B7280; font-style: italic; text-align: center;">
                                "Technologia jest darem, jeśli służy człowiekowi."
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #F9FAFB; padding: 30px; text-align: center;">
                            <p class="body-font" style="margin: 0 0 10px 0; font-size: 12px; color: #9CA3AF;">
                                &copy; {{ date('Y') }} . Wszelkie prawa zastrzeżone.<br>
                                Projekt tworzony z misją.
                            </p>
                            <p class="body-font" style="margin: 0; font-size: 12px; color: #9CA3AF;">
                                <a href="https://wspolnota.app"
                                    style="color: #4F46E5; text-decoration: underline;">Odwiedź stronę</a>
                                &bull;
                                <a href="#" style="color: #6B7280; text-decoration: underline;">Wypisz się</a>
                            </p>
                        </td>
                    </tr>
                </table>

                <div style="height: 40px;"></div>
            </td>
        </tr>
    </table>

</body>

</html>
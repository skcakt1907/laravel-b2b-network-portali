<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>DN Unity uyeliginiz onaylandi</title>
</head>
<body style="margin:0;padding:24px;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;color:#1b2430;line-height:1.6">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto">
    <tr>
        <td style="background:#13294b;border-radius:12px 12px 0 0;padding:24px;text-align:center">
            <span style="color:#fff;font-size:22px;font-weight:bold;letter-spacing:-.5px">
                DN <span style="color:#e0a52e">Unity</span>
            </span>
        </td>
    </tr>
    <tr>
        <td style="background:#ffffff;padding:28px;border:1px solid #e3e7ed;border-top:0;border-radius:0 0 12px 12px">

            <p style="margin:0 0 16px">Merhaba {{ $uye->name }},</p>

            <p style="margin:0 0 16px">
                DN Unity uyelik basvurunuz <strong>onaylandi</strong>. Artik is ortaklari
                agina, aylik toplantilara ve etkinliklere erisebilirsiniz.
            </p>

            <p style="margin:0 0 8px">Giris bilgileriniz:</p>

            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                   style="background:#f6f8fb;border:1px solid #e3e7ed;border-radius:8px;padding:16px;margin:0 0 16px">
                <tr>
                    <td style="padding:4px 0;color:#6b7684;font-size:14px">E-posta</td>
                    <td style="padding:4px 0;font-weight:bold">{{ $uye->email }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#6b7684;font-size:14px">Gecici sifre</td>
                    <td style="padding:4px 0;font-weight:bold;font-family:monospace;font-size:16px">{{ $geciciSifre }}</td>
                </tr>
            </table>

            <p style="margin:0 0 20px;font-size:14px;color:#b8821a;background:#fdf3de;border:1px solid #f0dcae;border-radius:8px;padding:12px">
                Guvenliginiz icin ilk girisinizde bu gecici sifreyi degistirmeniz istenecek.
                Bu e-postayi sifrenizi degistirdikten sonra silin.
            </p>

            <p style="margin:0 0 24px;text-align:center">
                <a href="{{ route('giris') }}"
                   style="display:inline-block;background:#13294b;color:#ffffff;text-decoration:none;
                          padding:12px 28px;border-radius:8px;font-weight:bold">
                    Giris yap
                </a>
            </p>

            <p style="margin:0;font-size:13px;color:#6b7684">
                Bu bag calismazsa adresi tarayiciniza yapistirin:<br>
                {{ route('giris') }}
            </p>

        </td>
    </tr>
    <tr>
        <td style="padding:16px;text-align:center;font-size:12px;color:#6b7684">
            DN Unity &middot; DN Kreatif ve Tatilim Sensin is ortaklari platformu
        </td>
    </tr>
</table>

</body>
</html>

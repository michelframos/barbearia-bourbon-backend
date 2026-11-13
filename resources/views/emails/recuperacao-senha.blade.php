<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperação de senha</title>
</head>
<body style="margin:0;padding:0;background:#111;color:#e8e8e8;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#111;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#1a1a1a;border:1px solid #333;border-radius:12px;padding:28px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#c9a227;">Bourbon Barbearia</p>
                            <h1 style="margin:0 0 16px;font-size:22px;color:#fff;">Recuperação de senha</h1>
                            <p style="margin:0 0 16px;line-height:1.5;color:#cfcfcf;">
                                O usuário <strong style="color:#fff;">{{ $userName }}</strong> solicitou a recuperação da senha de acesso ao sistema.
                            </p>
                            <p style="margin:0 0 24px;line-height:1.5;color:#cfcfcf;">
                                Para definir uma nova senha, use o link abaixo. Ele é de uso único e é válido por
                                <strong style="color:#fff;">{{ $expiresHours }} horas</strong>.
                            </p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $resetUrl }}"
                                   style="display:inline-block;background:#c9a227;color:#111;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;">
                                    Redefinir senha
                                </a>
                            </p>
                            <p style="margin:0 0 12px;line-height:1.5;font-size:13px;color:#999;word-break:break-all;">
                                Se o botão não funcionar, copie e cole este endereço no navegador:<br>
                                {{ $resetUrl }}
                            </p>
                            <p style="margin:0;line-height:1.5;font-size:13px;color:#999;">
                                Se você não solicitou esta recuperação, ignore este e-mail. Sua senha permanecerá a mesma.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

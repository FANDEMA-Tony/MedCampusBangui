<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificat signé</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:32px 0">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08)">

        <!-- HEADER -->
        <tr><td style="background:linear-gradient(135deg,#B8860B,#DAA520);padding:32px;text-align:center">
          <p style="margin:0 0 8px;font-size:36px">🏥</p>
          <h1 style="color:white;margin:0;font-size:22px;font-weight:900">MedCampus Bangui</h1>
          <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px">Faculté de Médecine et des Sciences de la Santé</p>
        </td></tr>

        <!-- BADGE -->
        <tr><td style="background:#FFFBEB;padding:20px;text-align:center;border-bottom:1px solid #FCD34D">
          <span style="font-size:28px">🎓</span>
          <p style="color:#B8860B;font-weight:800;font-size:16px;margin:8px 0 0">Votre certificat a été signé officiellement !</p>
        </td></tr>

        <!-- CORPS -->
        <tr><td style="padding:32px">
          <p style="color:#374151;font-size:15px;margin:0 0 16px">Bonjour <strong>{{ $nomEtudiant }}</strong>,</p>
          <p style="color:#4B5563;font-size:14px;margin:0 0 24px;line-height:1.6">
            Nous avons le plaisir de vous informer que votre certificat de réussite vient d'être signé officiellement par le responsable de la Faculté.
          </p>

          <!-- Carte certificat -->
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#FFFBEB;border-radius:12px;border:2px solid #DAA520;margin-bottom:24px">
            <tr><td style="padding:24px;text-align:center">
              <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#B8860B;text-transform:uppercase;letter-spacing:0.08em">Certificat de réussite</p>
              <p style="margin:0 0 16px;font-size:22px;font-weight:900;color:#1F2937">{{ $filiere }} — {{ $niveauValide }}</p>

              <table width="100%" cellpadding="0" cellspacing="0" style="text-align:left">
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid #FCD34D">
                    <span style="color:#92400E;font-size:13px">✍️ Signé par</span>
                  </td>
                  <td style="padding:8px 0;border-bottom:1px solid #FCD34D;text-align:right">
                    <strong style="color:#1F2937;font-size:13px">{{ $nomResponsable }}</strong>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0">
                    <span style="color:#92400E;font-size:13px">🔑 Code vérification</span>
                  </td>
                  <td style="padding:8px 0;text-align:right">
                    <strong style="color:#B8860B;font-size:12px;font-family:monospace">{{ $codeVerification }}</strong>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>

          <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px;text-align:center;margin-bottom:24px">
            <p style="color:#059669;font-weight:800;font-size:14px;margin:0">
              ✅ Connectez-vous pour télécharger votre certificat officiel en PDF
            </p>
          </div>

          <p style="color:#6B7280;font-size:13px;margin:0;line-height:1.6">
            Votre certificat contient un QR code unique permettant à toute institution de vérifier son authenticité. Félicitations pour votre réussite !
          </p>
        </td></tr>

        <!-- FOOTER -->
        <tr><td style="background:#F9FAFB;padding:20px;text-align:center;border-top:1px solid #E5E7EB">
          <p style="color:#9CA3AF;font-size:12px;margin:0">© {{ date('Y') }} MedCampus Bangui — Université de Bangui</p>
          <p style="color:#D1D5DB;font-size:11px;margin:4px 0 0">Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
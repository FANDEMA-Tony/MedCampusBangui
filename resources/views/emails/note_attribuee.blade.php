<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nouvelle note attribuée</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:32px 0">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08)">

        <!-- HEADER -->
        <tr><td style="background:linear-gradient(135deg,#0066CC,#0052A3);padding:32px;text-align:center">
          <p style="margin:0 0 8px;font-size:36px">🏥</p>
          <h1 style="color:white;margin:0;font-size:22px;font-weight:900;letter-spacing:0.05em">MedCampus Bangui</h1>
          <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px">Faculté de Médecine et des Sciences de la Santé</p>
        </td></tr>

        <!-- BADGE NOUVELLE NOTE -->
        <tr><td style="background:#EFF6FF;padding:20px;text-align:center;border-bottom:1px solid #DBEAFE">
          <span style="font-size:28px">📊</span>
          <p style="color:#0066CC;font-weight:800;font-size:16px;margin:8px 0 0">Nouvelle note attribuée</p>
        </td></tr>

        <!-- CORPS -->
        <tr><td style="padding:32px">
          <p style="color:#374151;font-size:15px;margin:0 0 16px">Bonjour <strong>{{ $nomEtudiant }}</strong>,</p>
          <p style="color:#4B5563;font-size:14px;margin:0 0 24px;line-height:1.6">
            Une nouvelle note vient d'être attribuée pour votre cours. Voici le détail :
          </p>

          <!-- Carte note -->
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#F9FAFB;border-radius:12px;border:1px solid #E5E7EB;margin-bottom:24px">
            <tr><td style="padding:20px">
              <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em">Détail de la note</p>
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid #F3F4F6">
                    <span style="color:#6B7280;font-size:13px">📚 Cours</span>
                  </td>
                  <td style="padding:8px 0;border-bottom:1px solid #F3F4F6;text-align:right">
                    <strong style="color:#1F2937;font-size:13px">{{ $titreCours }}</strong>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid #F3F4F6">
                    <span style="color:#6B7280;font-size:13px">🎯 Note obtenue</span>
                  </td>
                  <td style="padding:8px 0;border-bottom:1px solid #F3F4F6;text-align:right">
                    <strong style="color:{{ $note >= 10 ? '#059669' : '#DC143C' }};font-size:18px;font-weight:900">{{ $note }}/20</strong>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0">
                    <span style="color:#6B7280;font-size:13px">🏅 Mention</span>
                  </td>
                  <td style="padding:8px 0;text-align:right">
                    <strong style="color:#374151;font-size:13px">{{ $mention }}</strong>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>

          <!-- Badge résultat -->
          @if($note >= 10)
          <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px;text-align:center;margin-bottom:24px">
            <p style="color:#059669;font-weight:800;font-size:15px;margin:0">✅ Cours validé — Félicitations !</p>
          </div>
          @else
          <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:14px;text-align:center;margin-bottom:24px">
            <p style="color:#D97706;font-weight:800;font-size:15px;margin:0">⚠️ Cours non validé — Session de rattrapage disponible</p>
          </div>
          @endif

          <p style="color:#6B7280;font-size:13px;margin:0;line-height:1.6">
            Connectez-vous sur MedCampus pour consulter le détail de vos notes et suivre votre progression.
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
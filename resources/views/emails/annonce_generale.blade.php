<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Annonce importante</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:32px 0">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08)">

        <!-- HEADER -->
        <tr><td style="background:linear-gradient(135deg,#0066CC,#0052A3);padding:32px;text-align:center">
          <p style="margin:0 0 8px;font-size:36px">🏥</p>
          <h1 style="color:white;margin:0;font-size:22px;font-weight:900">MedCampus Bangui</h1>
          <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px">Faculté de Médecine et des Sciences de la Santé</p>
        </td></tr>

        <!-- BADGE -->
        <tr><td style="background:#FEF3C7;padding:20px;text-align:center;border-bottom:1px solid #FCD34D">
          <span style="font-size:28px">📢</span>
          <p style="color:#D97706;font-weight:800;font-size:16px;margin:8px 0 0">Annonce officielle</p>
        </td></tr>

        <!-- CORPS -->
        <tr><td style="padding:32px">
          <p style="color:#374151;font-size:15px;margin:0 0 16px">Bonjour <strong>{{ $nomDestinataire }}</strong>,</p>

          <!-- Titre annonce -->
          <div style="background:#EFF6FF;border-left:4px solid #0066CC;border-radius:0 8px 8px 0;padding:16px;margin-bottom:24px">
            <p style="color:#0066CC;font-weight:800;font-size:16px;margin:0">{{ $sujet }}</p>
          </div>

          <!-- Contenu -->
          <div style="background:#F9FAFB;border-radius:12px;border:1px solid #E5E7EB;padding:20px;margin-bottom:24px">
            <p style="color:#374151;font-size:14px;margin:0;line-height:1.8;white-space:pre-line">{{ $contenu }}</p>
          </div>

          <!-- Expéditeur -->
          <div style="display:flex;align-items:center;padding:14px;background:#F3F4F6;border-radius:10px;margin-bottom:24px">
            <p style="color:#6B7280;font-size:13px;margin:0">
              📌 Message envoyé par : <strong style="color:#374151">{{ $expediteur }}</strong>
            </p>
          </div>

          <p style="color:#6B7280;font-size:13px;margin:0;line-height:1.6">
            Pour toute question, connectez-vous sur MedCampus ou contactez l'administration directement.
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
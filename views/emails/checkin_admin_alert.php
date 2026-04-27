<?php
/**
 * Email template: Check-In Admin Alert
 *
 * Available variables:
 *   $memberName         string
 *   $memberCode         string
 *   $memberEmail        string
 *   $checkInDate        string
 *   $expiryDate         string
 *   $appName            string
 *   $appUrl             string
 *   $logoUrl            string
 *   $memberPhotoUrl     string
 */
declare(strict_types=1);
?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Member Check-In — <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; display: block; }
    body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #0a0a0a; }
    @media screen and (max-width: 600px) {
      .bento-col { display: block !important; width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; border: none !important; }
    }
  </style>
</head>
<body style="background-color:#0a0a0a; font-family: Arial, Helvetica, sans-serif; margin:0; padding:0;">

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0a0a0a;">
  <tr>
    <td align="center" style="padding: 24px 12px;">

      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background-color:#111111; border:1px solid #2a2a2a; border-radius:2px; overflow:hidden;">

        <!-- HEADER -->
        <tr>
          <td style="padding:24px 24px 16px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td width="1" style="padding-right:16px;">
                  <?php if (!empty($logoUrl)): ?>
                    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>" style="height:100px; width:auto; border-radius:2px; border:1px solid #333333; display:block;">
                  <?php endif; ?>
                </td>
                <td valign="middle">
                  <p style="font-size:11px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#e67e22; margin:0 0 6px;">Admin Notification</p>
                  <p style="font-size:22px; font-weight:700; color:#ffffff; margin:0 0 4px; line-height:1.2;">Member Check-In</p>
                  <p style="font-size:13px; color:#aaaaaa; margin:0; line-height:1.5;">A member has just checked in at <strong style="color:#ffffff;"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- DIVIDER -->
        <tr><td style="padding:0 24px;"><div style="height:1px; background-color:#2a2a2a;"></div></td></tr>

        <!-- BENTO: Photo + Details -->
        <tr>
          <td style="padding:20px 24px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <!-- Photo -->
                <td class="bento-col" width="45%" valign="top" style="padding-right:16px;">
                  <?php if (!empty($memberPhotoUrl)): ?>
                    <img src="<?= htmlspecialchars($memberPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; height:220px; object-fit:cover; border-radius:2px; border:1px solid #333333; display:block;">
                  <?php else: ?>
                    <div style="width:100%; height:220px; background:#0f0f0f; border-radius:2px; border:1px solid #333333; text-align:center;">
                      <p style="font-size:13px; color:#555555; margin:0; padding-top:100px;">No photo</p>
                    </div>
                  <?php endif; ?>
                </td>
                <!-- Details -->
                <td class="bento-col" width="55%" valign="top">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#1a1a1a; border:1px solid #2a2a2a; border-radius:2px; overflow:hidden;">
                    <tr>
                      <td style="padding:12px 16px; border-bottom:1px solid #222222;">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#555555; margin:0 0 4px;">Member Name</p>
                        <p style="font-size:14px; font-weight:600; color:#eeeeee; margin:0;"><?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?></p>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:12px 16px; border-bottom:1px solid #222222;">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#555555; margin:0 0 4px;">Member Code</p>
                        <p style="font-size:14px; font-weight:600; color:#eeeeee; margin:0; font-family:'Courier New', monospace;"><?= htmlspecialchars($memberCode, ENT_QUOTES, 'UTF-8') ?></p>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:12px 16px; border-bottom:1px solid #222222;">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#555555; margin:0 0 4px;">Member Email</p>
                        <p style="font-size:13px; color:#aaaaaa; margin:0;"><?= htmlspecialchars($memberEmail, ENT_QUOTES, 'UTF-8') ?></p>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:12px 16px; border-bottom:1px solid #222222;">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#555555; margin:0 0 4px;">Check-In Time</p>
                        <p style="font-size:14px; font-weight:600; color:#ffffff; margin:0;"><?= htmlspecialchars($checkInDate, ENT_QUOTES, 'UTF-8') ?></p>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:12px 16px;">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#555555; margin:0 0 4px;">Membership Expires</p>
                        <p style="font-size:14px; font-weight:700; color:#cccccc; margin:0;"><?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?></p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#0d0d0d; border-top:1px solid #2a2a2a; padding:16px 24px; text-align:center;">
            <p style="font-size:11px; color:#555555; margin:0 0 4px; letter-spacing:0.08em; text-transform:uppercase;"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> — Admin Alert</p>
            <p style="font-size:11px; color:#444444; margin:0; line-height:1.6;">
              Automated admin notification. Do not reply to this email.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>

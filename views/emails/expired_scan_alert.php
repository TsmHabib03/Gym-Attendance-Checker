<?php
/**
 * Email template: Expired Membership Scan Alert (Admin)
 *
 * Available variables:
 *   $memberName         string
 *   $memberCode         string
 *   $expiryDate         string
 *   $scannedAt          string
 *   $appName            string
 *   $appUrl             string
 *   $logoUrl            string
 */
declare(strict_types=1);
?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Expired Scan Alert — <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; display: block; }
    body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #0a0a0a; }
    a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
    @media screen and (max-width: 600px) {
      .logo-img { height: 72px !important; }
    }
  </style>
</head>
<body style="background-color:#0a0a0a; font-family: Arial, Helvetica, sans-serif; margin:0; padding:0; -webkit-font-smoothing:antialiased;">

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0a0a0a;">
  <tr>
    <td align="center" style="padding: 24px 12px;">

      <!-- SINGLE BOX -->
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background-color:#111111; border:1px solid #2a2a2a; border-radius:2px; overflow:hidden;">

        <!-- HEADER: Logo + Alert -->
        <tr>
          <td style="padding:24px 24px 16px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td width="1" style="padding-right:16px;">
                  <?php if (!empty($logoUrl)): ?>
                    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>" class="logo-img" style="height:100px; width:auto; border-radius:2px; border:1px solid #333333; display:block;">
                  <?php else: ?>
                    <div style="width:100px; height:100px; background:#1a1a1a; border-radius:2px; border:1px solid #333333; text-align:center;">
                      <span style="font-size:10px; color:#555; text-transform:uppercase; line-height:100px;">Logo</span>
                    </div>
                  <?php endif; ?>
                </td>
                <td valign="middle">
                  <p style="font-size:11px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#777777; margin:0 0 6px;">Admin Security Alert</p>
                  <p style="font-size:22px; font-weight:700; color:#ffffff; margin:0 0 4px; line-height:1.2;">Check-in Denied</p>
                  <p style="font-size:13px; color:#aaaaaa; margin:0; line-height:1.5;">An <strong style="color:#cccccc;">expired membership</strong> scan was attempted.</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- DIVIDER -->
        <tr>
          <td style="padding:0 24px;">
            <div style="height:1px; background-color:#2a2a2a;"></div>
          </td>
        </tr>

        <!-- DETAILS -->
        <tr>
          <td style="padding:20px 24px;">
            <p style="font-size:11px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#555555; margin:0 0 12px;">Incident Details</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#1a1a1a; border:1px solid #2a2a2a; border-radius:2px; overflow:hidden;">
              <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:#555555;">Member Name</td>
                      <td align="right" style="font-size:13px; font-weight:600; color:#eeeeee;"><?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:#555555;">Member Code</td>
                      <td align="right" style="font-size:13px; font-weight:600; color:#eeeeee; font-family:'Courier New', monospace;"><?= htmlspecialchars($memberCode, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:#555555;">Membership Expired</td>
                      <td align="right" style="font-size:13px; font-weight:700; color:#cccccc;"><?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:#555555;">Scan Attempted At</td>
                      <td align="right" style="font-size:13px; color:#aaaaaa;"><?= htmlspecialchars($scannedAt, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding:13px 16px;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase; color:#555555;">Scan Result</td>
                      <td align="right">
                        <span style="display:inline-block; padding:3px 10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); border-radius:2px; font-size:11px; font-weight:700; color:#cccccc; text-transform:uppercase; letter-spacing:0.08em;">Expired — Denied</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ACTION BOX -->
        <tr>
          <td style="padding:0 24px 20px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.10); border-left:3px solid #cccccc; border-radius:2px;">
              <tr>
                <td style="padding:14px 16px;">
                  <p style="font-size:13px; font-weight:700; color:#dddddd; margin:0 0 4px;">Action Required</p>
                  <p style="font-size:13px; color:#888888; margin:0; line-height:1.6;">
                    Contact this member to arrange a membership renewal. Until renewed, their QR code
                    will continue to be denied entry.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- CTA -->
        <tr>
          <td style="padding:0 24px 24px; text-align:center;">
            <a href="<?= htmlspecialchars($appUrl . '/members', ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block; padding:14px 36px; background-color:#ffffff; color:#0a0a0a; font-size:13px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; text-decoration:none; border-radius:2px;">View Member List</a>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#0d0d0d; border-top:1px solid #2a2a2a; padding:16px 24px; text-align:center;">
            <p style="font-size:11px; color:#555555; margin:0 0 4px; letter-spacing:0.08em; text-transform:uppercase;"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> — Admin Alert System</p>
            <p style="font-size:11px; color:#444444; margin:0; line-height:1.6;">
              This alert was automatically generated. Do not reply to this email.<br>
              For support, contact your system administrator.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>


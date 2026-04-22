<?php
/**
 * Email template: Membership Expiry Reminder
 *
 * Available variables:
 *   $memberName         string  — full member name
 *   $memberCode         string  — member code
 *   $expiryDate         string  — formatted expiry date (e.g. 2026-05-01)
 *   $daysUntilExpiry    int     — days remaining
 *   $appName            string  — brand name
 *   $appUrl             string  — application URL
 */
declare(strict_types=1);
?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Membership Expiry Reminder — <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
    a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
  </style>
</head>
<body style="
  background-color: #080808;
  font-family: 'DM Sans', Arial, sans-serif;
  margin: 0;
  padding: 0;
  -webkit-font-smoothing: antialiased;
">

<!-- Email wrapper -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#080808;">
  <tr>
    <td align="center" style="padding: 32px 16px;">

      <!-- Main container -->
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%;">

        <!-- ── HEADER ── -->
        <tr>
          <td style="
            background-color: #111111;
            border: 1px solid #2a2a2a;
            border-bottom: none;
            border-radius: 2px 2px 0 0;
            padding: 28px 32px 24px;
            text-align: left;
          ">
            <!-- Brand row -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td>
                  <p style="
                    font-family: Arial, sans-serif;
                    font-size: 22px;
                    font-weight: 700;
                    letter-spacing: 0.18em;
                    color: #ffffff;
                    margin: 0 0 4px;
                    text-transform: uppercase;
                  "><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></p>
                  <p style="
                    font-size: 11px;
                    color: #555555;
                    letter-spacing: 0.12em;
                    text-transform: uppercase;
                    margin: 0;
                  ">Membership Alert</p>
                </td>
                <td align="right" valign="middle">
                  <span style="
                    display: inline-block;
                    padding: 6px 14px;
                    background: rgba(251,191,36,0.10);
                    border: 1px solid rgba(251,191,36,0.30);
                    border-radius: 2px;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.12em;
                    color: #fbbf24;
                    text-transform: uppercase;
                    white-space: nowrap;
                  ">Expiring Soon</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── HERO BANNER ── -->
        <tr>
          <td style="
            background: linear-gradient(135deg, #1a1200 0%, #111111 60%);
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 32px 32px 28px;
            border-top: 1px solid rgba(251,191,36,0.20);
          ">
            <p style="
              font-size: 11px;
              font-weight: 700;
              letter-spacing: 0.16em;
              text-transform: uppercase;
              color: #fbbf24;
              margin: 0 0 10px;
            ">Membership Expiry Reminder</p>
            <p style="
              font-size: 26px;
              font-weight: 700;
              color: #ffffff;
              margin: 0 0 8px;
              line-height: 1.25;
            ">Hi <?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?>,</p>
            <p style="
              font-size: 15px;
              color: #aaaaaa;
              margin: 0;
              line-height: 1.6;
            ">
              Your gym membership is expiring in
              <strong style="color: #fbbf24;"><?= (int) $daysUntilExpiry ?> day<?= $daysUntilExpiry !== 1 ? 's' : '' ?></strong>.
              Renew now to keep your access uninterrupted.
            </p>
          </td>
        </tr>

        <!-- ── MEMBER DETAILS ── -->
        <tr>
          <td style="
            background-color: #111111;
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 0 32px 24px;
          ">
            <!-- Details block -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="
              background-color: #1a1a1a;
              border: 1px solid #2a2a2a;
              border-radius: 2px;
              overflow: hidden;
            ">
              <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Member</td>
                      <td align="right" style="font-size: 13px; font-weight: 600; color: #eeeeee;"><?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Member Code</td>
                      <td align="right" style="font-size: 13px; font-weight: 600; color: #eeeeee; font-family: 'Courier New', monospace;"><?= htmlspecialchars($memberCode, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Expiry Date</td>
                      <td align="right" style="font-size: 13px; font-weight: 700; color: #fbbf24;"><?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 14px 16px;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Days Remaining</td>
                      <td align="right">
                        <span style="
                          display: inline-block;
                          padding: 3px 10px;
                          background: rgba(251,191,36,0.10);
                          border: 1px solid rgba(251,191,36,0.25);
                          border-radius: 2px;
                          font-size: 12px;
                          font-weight: 700;
                          color: #fbbf24;
                        "><?= (int) $daysUntilExpiry ?> day<?= $daysUntilExpiry !== 1 ? 's' : '' ?></span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── CTA ── -->
        <tr>
          <td style="
            background-color: #111111;
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 8px 32px 32px;
            text-align: center;
          ">
            <p style="font-size: 14px; color: #888888; margin: 0 0 20px; line-height: 1.6;">
              Visit the gym front desk or contact us to renew your membership before it expires.
            </p>
            <a href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>" style="
              display: inline-block;
              padding: 14px 36px;
              background-color: #ffffff;
              color: #080808;
              font-size: 13px;
              font-weight: 700;
              letter-spacing: 0.12em;
              text-transform: uppercase;
              text-decoration: none;
              border-radius: 2px;
            ">Renew Membership</a>
          </td>
        </tr>

        <!-- ── DIVIDER / RULE ── -->
        <tr>
          <td style="
            background-color: #111111;
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 0 32px;
          ">
            <div style="height: 1px; background-color: #2a2a2a;"></div>
          </td>
        </tr>

        <!-- ── FOOTER ── -->
        <tr>
          <td style="
            background-color: #0d0d0d;
            border: 1px solid #2a2a2a;
            border-top: none;
            border-radius: 0 0 2px 2px;
            padding: 20px 32px;
            text-align: center;
          ">
            <p style="font-size: 11px; color: #555555; margin: 0 0 4px; letter-spacing: 0.08em; text-transform: uppercase;">
              <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <p style="font-size: 11px; color: #444444; margin: 0; line-height: 1.6;">
              This is an automated message. Please do not reply to this email.<br>
              If you believe this was sent in error, please contact gym management.
            </p>
          </td>
        </tr>

      </table>
      <!-- /Main container -->

    </td>
  </tr>
</table>

</body>
</html>

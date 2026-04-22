<?php
/**
 * Email template: Expired Membership Scan Alert (Admin)
 *
 * Available variables:
 *   $memberName         string  — full member name
 *   $memberCode         string  — member code
 *   $expiryDate         string  — membership end date
 *   $scannedAt          string  — date/time of scan attempt
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
  <title>Expired Scan Alert — <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
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
          ">
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
                  ">Admin Security Alert</p>
                </td>
                <td align="right" valign="middle">
                  <span style="
                    display: inline-block;
                    padding: 6px 14px;
                    background: rgba(248,113,113,0.10);
                    border: 1px solid rgba(248,113,113,0.30);
                    border-radius: 2px;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.12em;
                    color: #f87171;
                    text-transform: uppercase;
                    white-space: nowrap;
                  ">Access Denied</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── HERO BANNER ── -->
        <tr>
          <td style="
            background: linear-gradient(135deg, #200808 0%, #111111 60%);
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 32px 32px 28px;
            border-top: 1px solid rgba(248,113,113,0.20);
          ">
            <p style="
              font-size: 11px;
              font-weight: 700;
              letter-spacing: 0.16em;
              text-transform: uppercase;
              color: #f87171;
              margin: 0 0 10px;
            ">⚠ Expired Membership Scan Alert</p>
            <p style="
              font-size: 24px;
              font-weight: 700;
              color: #ffffff;
              margin: 0 0 8px;
              line-height: 1.3;
            ">Check-in Denied</p>
            <p style="
              font-size: 15px;
              color: #aaaaaa;
              margin: 0;
              line-height: 1.6;
            ">
              A member with an <strong style="color: #f87171;">expired membership</strong> attempted to scan in.
              Immediate action may be required.
            </p>
          </td>
        </tr>

        <!-- ── INCIDENT DETAILS ── -->
        <tr>
          <td style="
            background-color: #111111;
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 24px 32px;
          ">
            <p style="
              font-size: 11px;
              font-weight: 700;
              letter-spacing: 0.14em;
              text-transform: uppercase;
              color: #555555;
              margin: 0 0 12px;
            ">Incident Details</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="
              background-color: #1a1a1a;
              border: 1px solid #2a2a2a;
              border-radius: 2px;
              overflow: hidden;
            ">
              <tr>
                <td style="padding: 13px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Member Name</td>
                      <td align="right" style="font-size: 13px; font-weight: 600; color: #eeeeee;"><?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 13px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Member Code</td>
                      <td align="right" style="font-size: 13px; font-weight: 600; color: #eeeeee; font-family: 'Courier New', monospace;"><?= htmlspecialchars($memberCode, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 13px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Membership Expired</td>
                      <td align="right" style="font-size: 13px; font-weight: 700; color: #f87171;"><?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 13px 16px; border-bottom: 1px solid #222222;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Scan Attempted At</td>
                      <td align="right" style="font-size: 13px; color: #aaaaaa;"><?= htmlspecialchars($scannedAt, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding: 13px 16px;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size: 11px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: #555555;">Scan Result</td>
                      <td align="right">
                        <span style="
                          display: inline-block;
                          padding: 3px 10px;
                          background: rgba(248,113,113,0.10);
                          border: 1px solid rgba(248,113,113,0.25);
                          border-radius: 2px;
                          font-size: 11px;
                          font-weight: 700;
                          color: #f87171;
                          text-transform: uppercase;
                          letter-spacing: 0.08em;
                        ">Expired — Denied</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── ACTION ALERT BOX ── -->
        <tr>
          <td style="
            background-color: #111111;
            border-left: 1px solid #2a2a2a;
            border-right: 1px solid #2a2a2a;
            padding: 0 32px 24px;
          ">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="
              background: rgba(248,113,113,0.05);
              border: 1px solid rgba(248,113,113,0.20);
              border-left: 3px solid #f87171;
              border-radius: 2px;
            ">
              <tr>
                <td style="padding: 14px 16px;">
                  <p style="font-size: 13px; font-weight: 700; color: #fca5a5; margin: 0 0 4px;">Action Required</p>
                  <p style="font-size: 13px; color: #888888; margin: 0; line-height: 1.6;">
                    Contact this member to arrange a membership renewal. Until renewed, their QR code
                    will continue to be denied entry.
                  </p>
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
            padding: 0 32px 32px;
            text-align: center;
          ">
            <a href="<?= htmlspecialchars($appUrl . '/members', ENT_QUOTES, 'UTF-8') ?>" style="
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
            ">View Member List</a>
          </td>
        </tr>

        <!-- ── DIVIDER ── -->
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
              <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> — Admin Alert System
            </p>
            <p style="font-size: 11px; color: #444444; margin: 0; line-height: 1.6;">
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

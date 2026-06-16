<?php
$basePath = app_base_path();
$logoPath = '/assets/bhs_logo_v3.png';
$uLogo = ($basePath === '' ? '' : $basePath) . $logoPath;
$autoPrint = !empty($autoPrint);
$documentPageTitle = $documentPageTitle ?? 'Medicine receipt';
$bhcCenterName = 'Barangay Health Center';
$bhcLocation = 'Brgy. Balong Bato, San Juan City';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= h($documentPageTitle) ?></title>
    <link rel="icon" href="<?= h(app_url('/favicon.ico')) ?>" sizes="any" />
    <link rel="shortcut icon" href="<?= h(app_url('/favicon.ico')) ?>" />
    <style>
      :root {
        color-scheme: light;
        --text: #0f172a;
        --muted: #64748b;
        --border: #cbd5e1;
        --surface: #ffffff;
      }
      * { box-sizing: border-box; }
      body {
        margin: 0;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        color: var(--text);
        background: #f1f5f9;
      }
      .receipt-toolbar {
        max-width: 720px;
        margin: 0 auto;
        padding: 16px 16px 0;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
      }
      .receipt-toolbar-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
      .btn {
        display: inline-block;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        text-decoration: none;
        font-size: 14px;
        cursor: pointer;
      }
      .btn.ok {
        background: #2f6bff;
        border-color: #2f6bff;
        color: #fff;
      }
      .receipt-page-wrap {
        max-width: 720px;
        margin: 0 auto;
        padding: 16px;
      }
      .receipt-sheet {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 28px 32px;
      }
      .receipt-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #0f172a;
      }
      .receipt-logo {
        height: 56px;
        width: auto;
        margin-bottom: 8px;
      }
      .receipt-org {
        font-size: 20px;
        font-weight: 700;
        letter-spacing: 0.02em;
      }
      .receipt-subtitle {
        color: var(--muted);
        font-size: 13px;
        margin-top: 4px;
      }
      .receipt-doc-title {
        margin-top: 12px;
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
      }
      .receipt-meta {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 14px;
        margin-bottom: 18px;
      }
      .receipt-meta .col-label {
        width: 34%;
      }
      .receipt-meta td {
        padding: 7px 0;
        vertical-align: top;
        line-height: 1.45;
        word-break: break-word;
      }
      .receipt-meta td:first-child {
        color: var(--muted);
        padding-right: 14px;
        font-weight: 600;
      }
      .receipt-meta td:last-child {
        font-weight: 500;
      }
      .receipt-section-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0 8px;
      }
      .receipt-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 14px;
        margin-bottom: 18px;
      }
      .receipt-table .col-no { width: 6%; }
      .receipt-table .col-medicine { width: 28%; }
      .receipt-table .col-source { width: 22%; }
      .receipt-table .col-qty { width: 10%; }
      .receipt-table .col-unit { width: 14%; }
      .receipt-table .col-status { width: 20%; }
      .receipt-table th,
      .receipt-table td {
        border: 1px solid var(--border);
        padding: 8px 10px;
        text-align: left;
        vertical-align: top;
        word-break: break-word;
      }
      .receipt-table th.col-no,
      .receipt-table td.col-no,
      .receipt-table th.col-qty,
      .receipt-table td.col-qty,
      .receipt-table th.col-status,
      .receipt-table td.col-status {
        text-align: center;
      }
      .receipt-table th {
        background: #f8fafc;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .receipt-signatures {
        display: flex;
        gap: 24px;
        margin-top: 28px;
        font-size: 13px;
      }
      .receipt-signatures > .receipt-signature-block {
        flex: 1 1 0;
        min-width: 0;
      }
      .receipt-signature-block .receipt-subtitle {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--muted);
        line-height: 1.35;
        margin-top: 0;
      }
      .sig-line {
        border-bottom: 1px solid #334155;
        height: 32px;
        margin-top: 8px;
      }
      .sig-line--tall {
        height: 44px;
        margin-top: 10px;
      }
      .sig-name {
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.35;
      }
      .sig-name--role {
        margin-top: 4px;
        font-size: 11px;
        color: var(--muted);
      }
      .sig-name--placeholder {
        color: var(--muted);
        font-style: italic;
      }
      .receipt-footnote {
        margin-top: 18px;
        font-size: 11px;
        color: var(--muted);
        line-height: 1.5;
      }
      .receipt-preview-note {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        background: #fff7ed;
        border: 1px solid #fdba74;
        color: #9a3412;
        font-size: 12px;
      }
      @media print {
        html,
        body {
          width: 100%;
          margin: 0;
          padding: 0;
          background: #fff;
          color: #0f172a;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        .no-print { display: none !important; }
        .receipt-page-wrap {
          max-width: none;
          width: 100%;
          margin: 0;
          padding: 0 18mm;
          box-sizing: border-box;
        }
        .receipt-sheet {
          width: 100%;
          max-width: none;
          margin: 0;
          padding: 0 2mm;
          border: none;
          border-radius: 0;
          box-shadow: none;
          box-sizing: border-box;
        }
        .receipt-header {
          margin-bottom: 14px;
          padding-bottom: 12px;
          page-break-after: avoid;
        }
        .receipt-logo {
          height: 50px;
          margin-bottom: 6px;
        }
        .receipt-org {
          font-size: 18px;
        }
        .receipt-doc-title {
          font-size: 14px;
        }
        .receipt-meta,
        .receipt-table {
          font-size: 12px;
        }
        .receipt-meta td {
          padding: 5px 0;
        }
        .receipt-meta .col-label {
          width: 32%;
        }
        .receipt-table th,
        .receipt-table td {
          padding: 7px 8px;
        }
        .receipt-table th {
          background: #f1f5f9 !important;
        }
        .receipt-table thead {
          display: table-header-group;
        }
        .receipt-table tr {
          page-break-inside: avoid;
        }
        .receipt-section-title {
          margin-top: 4px;
          page-break-after: avoid;
        }
        .receipt-signatures {
          display: table;
          width: 100%;
          table-layout: fixed;
          border-collapse: separate;
          border-spacing: 16px 0;
          margin-top: 22px;
          page-break-inside: avoid;
        }
        .receipt-signatures > .receipt-signature-block {
          display: table-cell;
          vertical-align: top;
        }
        .receipt-signatures--cols-2 > .receipt-signature-block {
          width: 50%;
        }
        .receipt-signatures--cols-3 > .receipt-signature-block {
          width: 33.33%;
        }
        .sig-line {
          height: 28px;
        }
        .sig-line--tall {
          height: 36px;
        }
        .receipt-footnote {
          margin-top: 14px;
          font-size: 10px;
          page-break-inside: avoid;
        }
        @page {
          size: A4 portrait;
          margin: 14mm 0;
        }
      }
    </style>
  </head>
  <body>
    <div class="receipt-toolbar no-print">
      <div class="receipt-toolbar-actions">
        <button class="btn ok" type="button" onclick="window.print()">Print</button>
        <button class="btn" type="button" onclick="window.print()">Save as PDF</button>
      </div>
    </div>
    <div class="receipt-page-wrap">
      <?php require $receiptViewPath; ?>
    </div>
    <?php if ($autoPrint): ?>
      <script>window.addEventListener('load', function () { window.print(); });</script>
    <?php endif; ?>
  </body>
</html>

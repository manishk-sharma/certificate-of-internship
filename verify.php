<?php
require_once __DIR__ . '/certificate_fetcher.php';

$id = trim($_GET['id'] ?? '');
$cert = !empty($id) ? getCertificateFromSheet($sheetCsvUrl, $id) : null;
$baseUrl = getBaseUrl();

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $cert ? 'Verified: ' . e($cert['recipient']) : 'Certificate Verification' ?> - The IoT Academy</title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #293272;
      --accent: #e3a62d;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --success-bg: #ecfdf5;
      --success-text: #047857;
      --success-border: #a7f3d0;
      --danger-bg: #fef2f2;
      --danger-text: #b91c1c;
      --danger-border: #fecaca;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px 16px;
    }

    .verification-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
      max-width: 520px;
      width: 100%;
      padding: 36px 32px;
      text-align: center;
      position: relative;
    }

    .brand-logos {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--border);
    }

    .brand-logos img {
      height: 34px;
      width: auto;
      object-fit: contain;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 18px;
      border-radius: 999px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 16px;
    }

    .badge-verified {
      background: var(--success-bg);
      color: var(--success-text);
      border: 1px solid var(--success-border);
    }

    .badge-unverified {
      background: var(--danger-bg);
      color: var(--danger-text);
      border: 1px solid var(--danger-border);
    }

    h1 {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
    }

    .subtitle {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 24px;
    }

    .details-table {
      width: 100%;
      text-align: left;
      margin-bottom: 26px;
      background: #f8fafc;
      border-radius: 10px;
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .details-table tr:not(:last-child) {
      border-bottom: 1px solid var(--border);
    }

    .details-table th,
    .details-table td {
      padding: 12px 16px;
      font-size: 13px;
    }

    .details-table th {
      color: var(--text-muted);
      font-weight: 500;
      width: 36%;
    }

    .details-table td {
      color: var(--text);
      font-weight: 600;
    }

    .cert-id-badge {
      font-family: monospace;
      color: #b45309;
      background: #fef3c7;
      border: 1px solid #fde68a;
      padding: 2px 8px;
      border-radius: 4px;
      display: inline-block;
      font-weight: 600;
    }

    /* Actions and Buttons */
    .button-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 11px 20px;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-primary {
      background: var(--accent);
      color: #111827;
    }
    .btn-primary:hover {
      background: #d9931b;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background: #ffffff;
      color: #334155;
      border: 1px solid var(--border);
    }
    .btn-secondary:hover {
      background: #f1f5f9;
      color: #0f172a;
      transform: translateY(-1px);
    }

    /* Search Form for other IDs */
    .search-form {
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }

    .search-form label {
      display: block;
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 8px;
      text-align: left;
      font-weight: 500;
    }

    .input-row {
      display: flex;
      gap: 8px;
    }

    .search-input {
      flex: 1;
      padding: 10px 14px;
      border-radius: 8px;
      background: #ffffff;
      border: 1px solid var(--border);
      color: var(--text);
      font-family: inherit;
      font-size: 14px;
      outline: none;
      transition: all 0.2s;
    }
    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(41, 50, 114, 0.12);
    }

    .btn-search {
      padding: 10px 18px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-search:hover {
      background: #1f2658;
    }

    .footer-note {
      margin-top: 20px;
      font-size: 12px;
      color: var(--text-muted);
    }
  </style>
</head>
<body>

  <div class="verification-card">
    <!-- Brand Logos -->
    <div class="brand-logos">
      <img src="assets/iot-academy.png" alt="The IoT Academy" onerror="this.style.display='none'">
      <img src="assets/uct-nasscom.png" alt="UCT, member of NASSCOM" onerror="this.style.display='none'">
      <img src="assets/upskill-campus.png" alt="upSkill Campus" onerror="this.style.display='none'">
    </div>

    <?php if ($cert): ?>
      <!-- Verified Certificate Details -->
      <div class="status-badge badge-verified">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        Verified Authentic Certificate
      </div>

      <h1><?= e($cert['recipient']) ?></h1>
      <p class="subtitle"><?= e($cert['program']) ?></p>

      <table class="details-table" aria-label="Certificate details">
        <tr>
          <th>Certificate ID</th>
          <td><span class="cert-id-badge"><?= e($cert['certificationId']) ?></span></td>
        </tr>
        <tr>
          <th>Program</th>
          <td><?= e($cert['program']) ?></td>
        </tr>
        <tr>
          <th>Duration / Dates</th>
          <td><?= e($cert['dates']) ?></td>
        </tr>
        <tr>
          <th>Date of Issue</th>
          <td><?= e($cert['issueDate']) ?></td>
        </tr>
        <tr>
          <th>Authorized Signatory</th>
          <td><?= e($cert['signatory']) ?> (<?= e($cert['signatoryTitle']) ?>)</td>
        </tr>
      </table>

      <div class="button-group">
        <a href="index.php?id=<?= urlencode($cert['certificationId']) ?>" class="btn btn-primary" target="_blank">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
          View & Print Full Certificate
        </a>
      </div>

    <?php else: ?>
      <!-- Unverified / Invalid Certificate Notice -->
      <div class="status-badge badge-unverified">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        <?= !empty($id) ? 'Certificate Not Found' : 'Verify a Certificate' ?>
      </div>

      <h1><?= !empty($id) ? 'Invalid Certificate ID' : 'Certificate Verification Portal' ?></h1>
      <p class="subtitle">
        <?= !empty($id) 
          ? 'No issued certificate was found matching ID: <strong>' . e($id) . '</strong>. Please verify the ID or contact the issuing authority.' 
          : 'Enter an official Certification ID below to verify its authenticity and accreditation status.' ?>
      </p>

      <div class="button-group">
        <a href="dashboard.php" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          Open Certificate Dashboard
        </a>
        <a href="index.php" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
          Open Certificate Viewer
        </a>
      </div>
    <?php endif; ?>

    <!-- Quick Lookup Search Form for another ID -->
    

    <p class="footer-note">
      Verified by The IoT Academy in collaboration with UCT (NASSCOM member) & upSkill Campus.
    </p>
  </div>

</body>
</html>

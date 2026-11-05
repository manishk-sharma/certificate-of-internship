<?php
require_once __DIR__ . '/certificate_fetcher.php';

$baseUrl = getBaseUrl();
$flashMessage = null;

// Handle CSV Export requests
if (isset($_GET['export']) && $_GET['export'] === '1') {
    $certs = getAllCertificates(null);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="certificates_export_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Certification ID', 'Full Name', 'Program Title', 'Dates', 'Issue Date', 'Signatory', 'Signatory Title', 'Signatory Role']);
    foreach ($certs as $c) {
        fputcsv($output, [
            $c['certificationId'],
            $c['recipient'],
            $c['program'],
            $c['dates'],
            $c['issueDate'],
            $c['signatory'],
            $c['signatoryTitle'],
            $c['signatoryRole']
        ]);
    }
    fclose($output);
    exit;
}

// Handle Sample CSV Template download
if (isset($_GET['sample']) && $_GET['sample'] === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sample_certificates_template.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Certification ID', 'Full Name', 'Program Title', 'Dates', 'Issue Date', 'Signatory', 'Signatory Title', 'Signatory Role']);
    fputcsv($output, ['IOT-2026-005', 'Rohan Gupta', 'AI-Powered Embedded & IoT Program', '01/02/2026 to 31/03/2026', '05/04/2026', 'Kaushlendra Singh Sisodia', 'Chief Mentor, The IoT Academy', 'Director, Uniconverge Technologies']);
    fputcsv($output, ['IOT-2026-006', 'Ananya Deshmukh', 'Applied Machine Learning for Edge Devices', '15/02/2026 to 15/04/2026', '20/04/2026', 'Kaushlendra Singh Sisodia', 'Chief Mentor, The IoT Academy', 'Director, Uniconverge Technologies']);
    fclose($output);
    exit;
}

// Handle POST actions (Add, Import, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD NEW CERTIFICATE
    if ($action === 'add_certificate') {
        $certData = [
            'certificationId' => trim($_POST['certificationId'] ?? ''),
            'recipient'       => trim($_POST['recipient'] ?? ''),
            'program'         => trim($_POST['program'] ?? ''),
            'dates'           => trim($_POST['dates'] ?? ''),
            'issueDate'       => trim($_POST['issueDate'] ?? date('d/m/Y')),
            'signatory'       => trim($_POST['signatory'] ?? 'Kaushlendra Singh Sisodia'),
            'signatoryTitle'  => trim($_POST['signatoryTitle'] ?? 'Chief Mentor, The IoT Academy'),
            'signatoryRole'   => trim($_POST['signatoryRole'] ?? 'Director, Uniconverge Technologies'),
        ];

        if (empty($certData['certificationId']) || empty($certData['recipient'])) {
            $flashMessage = ['type' => 'error', 'text' => 'Certification ID and Recipient Name are required.'];
        } else {
            $result = saveCertificateToCsv($certData);
            if (!empty($result['success'])) {
                $verb = !empty($result['is_update']) ? 'updated' : 'added';
                $flashMessage = ['type' => 'success', 'text' => "Certificate '{$certData['certificationId']}' for {$certData['recipient']} was successfully {$verb}!"];
            } else {
                $flashMessage = ['type' => 'error', 'text' => $result['message'] ?? 'Could not save certificate.'];
            }
        }
    }

    // 2. IMPORT FROM CSV FILE
    elseif ($action === 'import_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $flashMessage = ['type' => 'error', 'text' => 'Please select a valid CSV file to upload.'];
        } else {
            $fileTmp = $_FILES['csv_file']['tmp_name'];
            $fileName = $_FILES['csv_file']['name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($ext !== 'csv') {
                $flashMessage = ['type' => 'error', 'text' => 'Invalid file format. Please upload a .csv file.'];
            } else {
                $mode = ($_POST['import_mode'] ?? 'append') === 'replace' ? 'replace' : 'append';
                $result = importCertificatesFromCsv($fileTmp, $mode);
                if (!empty($result['success'])) {
                    $flashMessage = [
                        'type' => 'success',
                        'text' => "Successfully imported {$result['count']} certificate(s)! Total active records: {$result['total']}."
                    ];
                } else {
                    $flashMessage = ['type' => 'error', 'text' => $result['message'] ?? 'Error importing CSV.'];
                }
            }
        }
    }

    // 3. DELETE CERTIFICATE
    elseif ($action === 'delete_certificate') {
        $delId = trim($_POST['delete_id'] ?? '');
        if (!empty($delId)) {
            $delRes = deleteCertificateFromCsv($delId);
            if (!empty($delRes['success'])) {
                $flashMessage = ['type' => 'success', 'text' => "Certificate '{$delId}' was successfully deleted."];
            } else {
                $flashMessage = ['type' => 'error', 'text' => $delRes['message'] ?? 'Failed to delete record.'];
            }
        }
    }
}

// Fetch all certificates
$certificates = getAllCertificates($sheetCsvUrl);

// Calculate Quick Stats
$totalCerts = count($certificates);
$programsList = array_filter(array_column($certificates, 'program'));
$uniquePrograms = count(array_unique($programsList));
$latestIssue = !empty($certificates) ? end($certificates)['issueDate'] : '-';

// Generate next suggested Certificate ID for the Add form
$maxNum = 0;
foreach ($certificates as $c) {
    if (preg_match('/(?:IOT-)?(\d{4})-(\d+)/i', $c['certificationId'], $m)) {
        $num = (int)$m[2];
        if ($num > $maxNum) $maxNum = $num;
    }
}
$nextSuggestedId = 'IOT-' . date('Y') . '-' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificate Management Dashboard - The IoT Academy</title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #293272;
      --primary-hover: #1f2658;
      --accent: #d9931b;
      --accent-hover: #b45309;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --border-subtle: #f1f5f9;
      --success: #10b981;
      --success-bg: #ecfdf5;
      --success-text: #047857;
      --danger: #ef4444;
      --danger-bg: #fef2f2;
      --danger-text: #b91c1c;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg);
      color: var(--text);
      padding: 24px 20px 60px;
      min-height: 100vh;
    }

    .container {
      max-width: 1220px;
      margin: 0 auto;
    }

    /* Top Navigation Bar */
    .top-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 20px;
      margin-bottom: 24px;
      border-bottom: 1px solid var(--border);
      flex-wrap: wrap;
      gap: 14px;
    }

    .brand-group {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-logo {
      height: 38px;
      width: auto;
      object-fit: contain;
    }

    .brand-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Flash Alerts */
    .flash-alert {
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      font-size: 14px;
      font-weight: 500;
      animation: fadeIn 0.25s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .alert-success {
      background: var(--success-bg);
      color: var(--success-text);
      border: 1px solid #a7f3d0;
    }

    .alert-error {
      background: var(--danger-bg);
      color: var(--danger-text);
      border: 1px solid #fecaca;
    }

    .alert-close {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: inherit;
      opacity: 0.7;
    }
    .alert-close:hover { opacity: 1; }

    /* Stats Overview Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 18px 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .stat-icon-blue { background: #eff6ff; color: #2563eb; }
    .stat-icon-amber { background: #fef3c7; color: #d97706; }
    .stat-icon-emerald { background: #ecfdf5; color: #059669; }

    .stat-meta .stat-label {
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .stat-meta .stat-val {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
      line-height: 1.2;
    }

    /* Actions Bar */
    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 14px;
    }

    .search-box {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 16px;
      color: var(--text);
      font-family: inherit;
      font-size: 14px;
      min-width: 320px;
      outline: none;
      transition: all 0.2s;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }
    .search-box::placeholder {
      color: #94a3b8;
    }
    .search-box:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(41, 50, 114, 0.12);
    }

    .action-buttons {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 15px;
      font-size: 13px;
      font-weight: 600;
      font-family: inherit;
      border-radius: 8px;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
      user-select: none;
    }

    .btn svg {
      flex-shrink: 0;
    }

    .btn-primary,
    .btn-gold {
      background: #e3a62d;
      color: #111827;
      font-weight: 600;
    }
    .btn-primary:hover,
    .btn-gold:hover {
      background: #d9931b;
      transform: translateY(-1px);
    }

    .btn-blue {
      background: var(--primary);
      color: #ffffff;
    }
    .btn-blue:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
    }

    .btn-outline {
      background: #ffffff;
      color: #334155;
      border: 1px solid var(--border);
    }
    .btn-outline:hover {
      background: #f1f5f9;
      color: #0f172a;
      border-color: #cbd5e1;
      transform: translateY(-1px);
    }

    .btn-verify {
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
    }
    .btn-verify:hover {
      background: #d1fae5;
      color: #065f46;
      transform: translateY(-1px);
    }

    .btn-delete {
      background: #ffffff;
      color: #ef4444;
      border: 1px solid #fecaca;
      padding: 6px 10px;
    }
    .btn-delete:hover {
      background: #fef2f2;
      border-color: #f87171;
      transform: translateY(-1px);
    }

    /* Main Table Card */
    .card {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid var(--border);
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    th {
      background: #f8fafc;
      padding: 14px 18px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #475569;
      font-weight: 600;
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 15px 18px;
      font-size: 14px;
      border-bottom: 1px solid var(--border-subtle);
      color: #334155;
    }

    tr:hover td {
      background: #f8fafc;
    }

    .cert-id {
      font-family: monospace;
      color: #b45309;
      background: #fef3c7;
      border: 1px solid #fde68a;
      padding: 3px 8px;
      border-radius: 5px;
      font-weight: 600;
      font-size: 13px;
      display: inline-block;
    }

    .name-col {
      color: var(--text);
      font-weight: 600;
    }

    .actions {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }

    .no-results {
      text-align: center;
      padding: 48px 20px;
      color: var(--text-muted);
      font-size: 15px;
    }

    /* Modals (White Theme) */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-backdrop.open {
      display: flex;
    }

    .modal-dialog {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 14px;
      max-width: 600px;
      width: 100%;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      animation: modalSlide 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalSlide {
      from { transform: translateY(12px) scale(0.98); opacity: 0; }
      to { transform: translateY(0) scale(1); opacity: 1; }
    }

    .modal-header {
      padding: 18px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      line-height: 1;
      cursor: pointer;
      color: var(--text-muted);
    }
    .modal-close:hover { color: var(--text); }

    .modal-body {
      padding: 22px;
      max-height: 75vh;
      overflow-y: auto;
    }

    .form-group {
      margin-bottom: 16px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-group label {
      font-size: 13px;
      font-weight: 600;
      color: #334155;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 10px 14px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: #f8fafc;
      color: var(--text);
      font-family: inherit;
      font-size: 14px;
      outline: none;
      transition: all 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus {
      background: #ffffff;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(41, 50, 114, 0.12);
    }

    .input-addon-group {
      display: flex;
      gap: 6px;
    }
    .input-addon-group input {
      flex: 1;
    }

    .modal-footer {
      padding: 16px 22px;
      background: #f8fafc;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    /* Import Drop Zone */
    .file-dropzone {
      border: 2px dashed #cbd5e1;
      border-radius: 10px;
      padding: 24px;
      text-align: center;
      background: #f8fafc;
      cursor: pointer;
      transition: border-color 0.2s;
    }
    .file-dropzone:hover {
      border-color: var(--primary);
      background: #f1f5f9;
    }
    .file-dropzone input[type="file"] {
      display: none;
    }
    .file-dropzone svg {
      color: #64748b;
      margin-bottom: 8px;
    }

    .import-options {
      margin-top: 18px;
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 14px;
    }
    .import-options label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #334155;
      font-weight: 500;
      margin-bottom: 6px;
      cursor: pointer;
    }
    .import-options label:last-child {
      margin-bottom: 0;
    }

    .csv-format-help {
      margin-top: 14px;
      font-size: 12px;
      color: var(--text-muted);
      line-height: 1.5;
      background: #f8fafc;
      padding: 10px 12px;
      border-radius: 6px;
      border: 1px solid var(--border-subtle);
    }

    @media (max-width: 768px) {
      .table-wrapper { overflow-x: auto; }
      .top-nav, .action-bar { flex-direction: column; align-items: stretch; }
      .search-box { width: 100%; min-width: 0; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Top Global Navigation Bar linking all 3 files -->
    <nav class="top-nav" aria-label="Main Navigation">
      <div class="brand-group">
        <img src="assets/iot-academy.png" alt="The IoT Academy" class="brand-logo" onerror="this.style.display='none'">
        <span class="brand-title">Certificate Management Portal</span>
      </div>
      <div class="nav-links">
        <a href="index.php" class="btn btn-outline" title="Open certificate viewer canvas">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
          Certificate Viewer
        </a>
        <a href="verify.php" class="btn btn-outline" title="Verify certificate authenticity">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Verification Portal
        </a>
      </div>
    </nav>

    <!-- Flash Status Messages -->
    <?php if ($flashMessage): ?>
      <div class="flash-alert alert-<?= $flashMessage['type'] === 'success' ? 'success' : 'error' ?>" role="alert">
        <span><?= e($flashMessage['text']) ?></span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close alert">&times;</button>
      </div>
    <?php endif; ?>

    <!-- Stats Overview Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon stat-icon-amber">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <div class="stat-meta">
          <div class="stat-label">Total Certificates</div>
          <div class="stat-val" id="statTotal"><?= $totalCerts ?></div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <div class="stat-meta">
          <div class="stat-label">Programs Enrolled</div>
          <div class="stat-val"><?= $uniquePrograms ?></div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon stat-icon-emerald">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        </div>
        <div class="stat-meta">
          <div class="stat-label">Latest Issue Date</div>
          <div class="stat-val" style="font-size: 16px;"><?= e($latestIssue) ?></div>
        </div>
      </div>
    </div>

    <!-- Action & Search Toolbar -->
    <div class="action-bar">
      <input type="text" id="searchInput" class="search-box" placeholder="Search by recipient, ID, or program..." aria-label="Search certificates">

      <div class="action-buttons">
        <!-- Add New Certificate Button -->
        <button type="button" class="btn btn-primary" id="openAddModalBtn" title="Create a new certificate record">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add New Certificate
        </button>

        <!-- Import CSV Button -->
        <button type="button" class="btn btn-outline" id="openImportModalBtn" title="Import multiple records from CSV file">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          Import CSV
        </button>

        <!-- Export CSV Link -->
        <a href="dashboard.php?export=1" class="btn btn-outline" title="Export current certificates database to CSV">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Export CSV
        </a>
      </div>
    </div>

    <!-- Certificate Data Table -->
    <div class="card">
      <div class="table-wrapper">
        <table id="certTable">
          <thead>
            <tr>
              <th>Certification ID</th>
              <th>Recipient Name</th>
              <th>Program</th>
              <th>Dates</th>
              <th>Issue Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($certificates as $cert): ?>
              <?php
                $certId = $cert['certificationId'] ?? $cert['Certification ID'] ?? '';
                $name   = $cert['recipient'] ?? $cert['Full Name'] ?? '';
                $prog   = $cert['program'] ?? $cert['Program Title'] ?? '';
                $dates  = $cert['dates'] ?? $cert['Dates'] ?? '';
                $issue  = $cert['issueDate'] ?? $cert['Issue Date'] ?? '';
              ?>
              <tr>
                <td><span class="cert-id"><?= e($certId) ?></span></td>
                <td class="name-col"><?= e($name) ?></td>
                <td><?= e($prog) ?></td>
                <td><?= e($dates) ?></td>
                <td><?= e($issue) ?></td>
                <td>
                  <div class="actions">
                    <a class="btn btn-gold" href="index.php?id=<?= urlencode($certId) ?>" target="_blank" title="View and print formatted certificate">
                      View / Print
                    </a>
                    <a class="btn btn-outline" href="index.php?id=<?= urlencode($certId) ?>&openEditor=1" target="_blank" title="Edit this certificate in live canvas">
                      Edit
                    </a>
                    <a class="btn btn-verify" href="verify.php?id=<?= urlencode($certId) ?>" target="_blank" title="Verify certificate authenticity">
                      Verify
                    </a>
                    <!-- Quick Delete Form -->
                    <form method="POST" action="dashboard.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete certificate <?= e($certId) ?> for <?= e($name) ?>?');">
                      <input type="hidden" name="action" value="delete_certificate">
                      <input type="hidden" name="delete_id" value="<?= e($certId) ?>">
                      <button type="submit" class="btn btn-delete" title="Delete certificate">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div id="noResults" class="no-results" style="display: none;">
          No certificates matching your search criteria.
        </div>
      </div>
    </div>
  </div>

  <!-- 1. ADD NEW CERTIFICATE MODAL -->
  <div class="modal-backdrop" id="addModal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="addModalTitle">
      <div class="modal-header">
        <h3 id="addModalTitle">Add New Certificate</h3>
        <button type="button" class="modal-close" id="closeAddModalBtn" aria-label="Close modal">&times;</button>
      </div>
      <form method="POST" action="dashboard.php">
        <input type="hidden" name="action" value="add_certificate">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label for="new-certId">Certification ID *</label>
              <div class="input-addon-group">
                <input type="text" id="new-certId" name="certificationId" value="<?= e($nextSuggestedId) ?>" placeholder="e.g. IOT-2026-005" required>
                <button type="button" class="btn btn-outline" id="autoIdBtn" title="Auto-increment ID">Auto</button>
              </div>
            </div>
            <div class="form-group">
              <label for="new-issueDate">Issue Date *</label>
              <input type="text" id="new-issueDate" name="issueDate" value="<?= date('d/m/Y') ?>" placeholder="DD/MM/YYYY" required>
            </div>
          </div>

          <div class="form-group">
            <label for="new-recipient">Recipient Full Name *</label>
            <input type="text" id="new-recipient" name="recipient" placeholder="e.g. Rohan Gupta" required>
          </div>

          <div class="form-group">
            <label for="new-program">Program Title *</label>
            <input type="text" id="new-program" name="program" list="programsList" placeholder="e.g. AI-Powered Embedded & IoT Program" required>
            <datalist id="programsList">
              <option value="AI-Powered Embedded & IoT Program">
              <option value="Advanced Robotics & Embedded Systems">
              <option value="Industrial IoT & Cloud Computing">
              <option value="Applied Machine Learning for Edge Devices">
              <option value="Full Stack Embedded Developer Program">
            </datalist>
          </div>

          <div class="form-group">
            <label for="new-dates">Duration / Dates (From - To) *</label>
            <input type="text" id="new-dates" name="dates" placeholder="e.g. 01/02/2026 to 31/03/2026" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="new-signatory">Authorized Signatory</label>
              <input type="text" id="new-signatory" name="signatory" value="Kaushlendra Singh Sisodia" required>
            </div>
            <div class="form-group">
              <label for="new-signatoryTitle">Signatory Title</label>
              <input type="text" id="new-signatoryTitle" name="signatoryTitle" value="Chief Mentor, The IoT Academy" required>
            </div>
          </div>

          <div class="form-group">
            <label for="new-signatoryRole">Signatory Role / Organization</label>
            <input type="text" id="new-signatoryRole" name="signatoryRole" value="Director, Uniconverge Technologies" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancelAddModalBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Certificate</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. IMPORT CSV MODAL -->
  <div class="modal-backdrop" id="importModal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="importModalTitle">
      <div class="modal-header">
        <h3 id="importModalTitle">Import Certificates from CSV</h3>
        <button type="button" class="modal-close" id="closeImportModalBtn" aria-label="Close modal">&times;</button>
      </div>
      <form method="POST" action="dashboard.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import_csv">
        <div class="modal-body">
          <!-- Dropzone -->
          <label class="file-dropzone" for="csv_file_input" id="dropzoneLabel">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;" id="fileChosenText">Choose CSV file or drag & drop here</div>
            <div style="font-size: 12px; color: #64748b;">Supports .csv files with standard comma-separated format</div>
            <input type="file" name="csv_file" id="csv_file_input" accept=".csv" required>
          </label>

          <!-- Import Mode Selection -->
          <div class="import-options">
            <div style="font-size: 13px; font-weight: 600; margin-bottom: 8px;">Import Mode:</div>
            <label>
              <input type="radio" name="import_mode" value="append" checked>
              <span><strong>Append & Update:</strong> Add new records and update any existing IDs</span>
            </label>
            <label>
              <input type="radio" name="import_mode" value="replace">
              <span><strong>Replace Entire Registry:</strong> Overwrite all certificates with this file</span>
            </label>
          </div>

          <!-- Format Guidance & Template Download -->
          <div class="csv-format-help">
            <strong>Expected Column Headers:</strong><br>
            <code>Certification ID, Full Name, Program Title, Dates, Issue Date, Signatory, Signatory Title, Signatory Role</code>
            <div style="margin-top: 8px;">
              <a href="dashboard.php?sample=1" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download Sample CSV Template
              </a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancelImportModalBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload & Import</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Live Search Filter
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('certTable');
    const tbody = table.querySelector('tbody');
    const noResults = document.getElementById('noResults');
    const statTotal = document.getElementById('statTotal');

    searchInput.addEventListener('input', function(e) {
      const query = e.target.value.toLowerCase().trim();
      const rows = tbody.querySelectorAll('tr');
      let visibleCount = 0;

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const matches = text.includes(query);
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
      });

      noResults.style.display = visibleCount === 0 ? 'block' : 'none';
      if (statTotal) statTotal.textContent = visibleCount;
    });

    // Modal Control Helper
    function setupModal(modalId, openBtnId, closeBtnId, cancelBtnId) {
      const modal = document.getElementById(modalId);
      const openBtn = document.getElementById(openBtnId);
      const closeBtn = document.getElementById(closeBtnId);
      const cancelBtn = document.getElementById(cancelBtnId);

      const openModal = () => {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
      };
      const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
      };

      if (openBtn) openBtn.addEventListener('click', openModal);
      if (closeBtn) closeBtn.addEventListener('click', closeModal);
      if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
          closeModal();
        }
      });
    }

    setupModal('addModal', 'openAddModalBtn', 'closeAddModalBtn', 'cancelAddModalBtn');
    setupModal('importModal', 'openImportModalBtn', 'closeImportModalBtn', 'cancelImportModalBtn');

    // Auto-generate next suggested ID
    const autoIdBtn = document.getElementById('autoIdBtn');
    const certIdInput = document.getElementById('new-certId');
    if (autoIdBtn && certIdInput) {
      autoIdBtn.addEventListener('click', () => {
        const d = new Date();
        const year = d.getFullYear();
        const rand = Math.floor(100 + Math.random() * 900);
        certIdInput.value = `IOT-${year}-${rand}`;
      });
    }

    // Dropzone file selection label update
    const fileInput = document.getElementById('csv_file_input');
    const fileChosenText = document.getElementById('fileChosenText');
    if (fileInput && fileChosenText) {
      fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
          fileChosenText.textContent = 'Selected: ' + e.target.files[0].name;
          fileChosenText.style.color = '#047857';
        }
      });
    }
  </script>
</body>
</html>

<?php
require_once __DIR__ . '/certificate_fetcher.php';

// Base certificate defaults matching reference
$defaultCertificate = [
    'recipient'       => 'Name',
    'program'         => 'Data Analytics with Gen AI & Agentic AI Intern',
    'duration'        => '6 Weeks',
    'company'         => 'UniConverge Technologies Pvt. Ltd.',
    'dates'           => 'xx xx xxxx to xx xx xxxx',
    'certificationId' => 'UCTxxxxxx',
    'issueDate'       => 'xx xx xxxx',
    'signatory'       => 'Kaushlendra Singh Sisodia',
    'signatoryRole'   => 'Director, UniConverge Technologies Pvt. Ltd.',
    'signatoryTitle'  => 'Chief Mentor, The IoT Academy',
];

$targetId = trim($_REQUEST['id'] ?? $_REQUEST['certificationId'] ?? '');
if (!empty($targetId)) {
    $fetched = getCertificateFromSheet($sheetCsvUrl, $targetId);
    if ($fetched) {
        $defaultCertificate = array_merge($defaultCertificate, $fetched);
    }
}

$certificate = array_merge($defaultCertificate, array_intersect_key($_REQUEST, $defaultCertificate));

function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

$baseUrl = getBaseUrl();
$verificationUrl = $baseUrl . '/verify.php?id=' . urlencode($certificate['certificationId']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificate of Internship - <?= e($certificate['recipient']) ?></title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="stylesheet" href="certificate.css">
</head>
<body>

  <!-- Floating Navigation Toolbar -->
  <nav class="toolbar" id="mainToolbar" aria-label="Certificate Actions">
    <a href="dashboard.php" class="btn btn-secondary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span class="btn-text">Dashboard</span>
    </a>
    <a href="<?= e($verificationUrl) ?>" target="_blank" class="btn btn-secondary" id="verifyToolbarBtn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      <span class="btn-text">Verify</span>
    </a>
    <button type="button" class="btn btn-icon active" id="fitToggleBtn">
      <span class="btn-text">Fit Screen</span>
    </button>
    <button type="button" class="btn btn-primary" id="toggleEditBtn">
      <span class="btn-text">Edit</span>
    </button>
    <button type="button" class="btn btn-secondary" onclick="window.print()">
      <span class="btn-text">Print</span>
    </button>
  </nav>

  <!-- Live Editor Drawer -->
  <div class="backdrop" id="backdrop"></div>
  <aside class="editor-drawer <?= !empty($_GET['openEditor']) ? 'open' : '' ?>" id="editorDrawer">
    <div class="editor-header">
      <h2>Edit Internship Certificate</h2>
      <button type="button" class="close-btn" id="closeDrawerBtn">&times;</button>
    </div>
    <form class="editor-form" method="POST" action="">
      <div class="form-group">
        <label>Recipient Name</label>
        <input type="text" id="input-recipient" name="recipient" value="<?= e($certificate['recipient']) ?>" required>
      </div>
      <div class="form-group">
        <label>Duration</label>
        <input type="text" id="input-duration" name="duration" value="<?= e($certificate['duration']) ?>" placeholder="e.g. 6 Weeks">
      </div>
      <div class="form-group">
        <label>Internship Domain / Role</label>
        <input type="text" id="input-program" name="program" value="<?= e($certificate['program']) ?>" required>
      </div>
      <div class="form-group">
        <label>Dates (From to To)</label>
        <input type="text" id="input-dates" name="dates" value="<?= e($certificate['dates']) ?>" required>
      </div>
      <div class="form-group">
        <label>Certificate ID</label>
        <input type="text" id="input-certificationId" name="certificationId" value="<?= e($certificate['certificationId']) ?>" required>
      </div>
      <div class="form-group">
        <label>Issue Date</label>
        <input type="text" id="input-issueDate" name="issueDate" value="<?= e($certificate['issueDate']) ?>" required>
      </div>
      <div class="form-group">
        <label>Signatory Name</label>
        <input type="text" id="input-signatory" name="signatory" value="<?= e($certificate['signatory']) ?>" required>
      </div>
      <div class="form-group">
        <label>Signatory Role</label>
        <input type="text" id="input-signatoryRole" name="signatoryRole" value="<?= e($certificate['signatoryRole']) ?>" required>
      </div>
      <div class="form-group">
        <label>Signatory Title</label>
        <input type="text" id="input-signatoryTitle" name="signatoryTitle" value="<?= e($certificate['signatoryTitle']) ?>" required>
      </div>
      <div style="display: flex; gap: 10px; margin-top: 10px;">
        <button type="submit" class="btn btn-primary" style="flex: 1;">Save & Apply</button>
        <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
      </div>
    </form>
  </aside>

  <!-- Certificate Canvas Wrapper -->
  <div class="page-shell">
    <article class="certificate" aria-label="Certificate of Internship">
      
      <!-- High-Accuracy SVG Layer (Pure White Base + Single Inset Gold Frame + Corner Ribbons) -->
      <svg class="frame-artwork" viewBox="0 0 1024 700" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="gold-band-left" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#fae298" />
            <stop offset="45%" stop-color="#e3a731" />
            <stop offset="100%" stop-color="#b67310" />
          </linearGradient>

          <linearGradient id="gold-band-right" x1="100%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#fae298" />
            <stop offset="45%" stop-color="#e3a731" />
            <stop offset="100%" stop-color="#b67310" />
          </linearGradient>

          <linearGradient id="royal-blue-grad-left" x1="0%" y1="100%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#141f4f" />
            <stop offset="60%" stop-color="#1f3579" />
            <stop offset="100%" stop-color="#2c4d9a" />
          </linearGradient>

          <linearGradient id="royal-blue-grad-right" x1="100%" y1="100%" x2="0%" y2="0%">
            <stop offset="0%" stop-color="#141f4f" />
            <stop offset="60%" stop-color="#1f3579" />
            <stop offset="100%" stop-color="#2c4d9a" />
          </linearGradient>
        </defs>

        <!-- Pure White Certificate Document Canvas -->
        <rect x="0" y="0" width="1024" height="700" fill="#ffffff" />

        <!-- Single Elegant Inset Gold Rectangular Border Frame -->
        <rect x="24" y="24" width="976" height="652" fill="none" stroke="#e0be84" stroke-width="1.8" />

        <!-- LEFT CORNER GEOMETRY (Anchored directly to bottom-left corner of the certificate) -->
        <g class="corner-left">
          <!-- Deep Dark Navy Base Triangle -->
          <polygon points="0,480 0,700 160,700" fill="#04092b" />
          <!-- Royal Blue Facet Wedge -->
          <polygon points="0,380 0,480 160,700 230,700" fill="url(#royal-blue-grad-left)" />
          <!-- Gold Diagonal Ribbon Stripe -->
          <polygon points="0,340 0,380 230,700 255,700" fill="url(#gold-band-left)" />
        </g>

        <!-- RIGHT CORNER GEOMETRY (Exact Symmetrical Coordinates) -->
        <g class="corner-right">
          <!-- Deep Dark Navy Base Triangle -->
          <polygon points="1024,480 1024,700 864,700" fill="#04092b" />
          <!-- Royal Blue Facet Wedge -->
          <polygon points="1024,380 1024,480 864,700 794,700" fill="url(#royal-blue-grad-right)" />
          <!-- Gold Diagonal Ribbon Stripe -->
          <polygon points="1024,340 1024,380 794,700 769,700" fill="url(#gold-band-right)" />
        </g>
      </svg>

      <!-- Main Paper Content -->
      <div class="paper">
        
        <!-- Header Logos (IoT Academy, UCT NASSCOM, upSkill Campus) -->
        <header class="brand-header">
          <img class="brand brand-iot" src="assets/iot-academy.png" alt="The IoT Academy">
          <img class="brand brand-uct" src="assets/uct-nasscom.png" alt="UCT, Member of NASSCOM">
          <img class="brand brand-upskill" src="assets/upskill-campus.png" alt="upSkill Campus">
        </header>

        <!-- Crisp Swallowtail Ribbon Banner with Gold Border -->
        <div class="ribbon-banner">
          <svg viewBox="0 0 520 70" class="ribbon-svg" preserveAspectRatio="none" aria-hidden="true">
            <defs>
              <linearGradient id="ribbon-navy" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#141c48" />
                <stop offset="50%" stop-color="#24387d" />
                <stop offset="100%" stop-color="#141c48" />
              </linearGradient>
              <linearGradient id="ribbon-gold-stroke" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fae298" />
                <stop offset="50%" stop-color="#dfa837" />
                <stop offset="100%" stop-color="#b67310" />
              </linearGradient>
            </defs>
            <!-- Swallowtail notched ribbon body with gold outline -->
            <polygon points="2,2 518,2 497,35 518,68 2,68 23,35" fill="url(#ribbon-navy)" stroke="url(#ribbon-gold-stroke)" stroke-width="3" stroke-linejoin="miter" />
          </svg>
          <h1 class="ribbon-title">Certificate of Internship</h1>
        </div>

        <!-- Typography & Certificate Body Text -->
        <main class="certificate-body">
          <p class="certify-lead">This is to Certify that</p>
          
          <h2 class="recipient-name" id="display-recipient"><?= e($certificate['recipient']) ?></h2>

          <div class="diamond-divider">
            <span class="d-icon">◆</span>
            <div class="d-line"></div>
            <span class="d-icon">◆</span>
          </div>

          <p class="cert-text">
            has successfully completed a <span id="display-duration"><?= e($certificate['duration']) ?></span> internship as an <strong id="display-program"><?= e($certificate['program']) ?></strong> at
            <br>
            <strong class="company-text">“<?= e($certificate['company']) ?>”</strong> from <span id="display-dates"><?= e($certificate['dates']) ?></span>. During the internship,
            <br>
            she showed dedication, learned quickly, and performed assigned tasks well.
            <br>
            We wish her all the best in her future endeavors
          </p>

          <p class="cert-id-row">
            Certificate ID• <span class="cert-id-val" id="display-certificationId"><?= e($certificate['certificationId']) ?></span>
          </p>
        </main>

        <!-- Footer Section (Issue Date and Kaushlendra Singh Sisodia Signatory with Stamp) -->
        <footer class="certificate-footer">
          <!-- Left: Issue Date Line -->
          <div class="footer-left">
            <div class="sign-line"></div>
            <p class="date-text">
              Issue Date• <span id="display-issueDate"><?= e($certificate['issueDate']) ?></span>
            </p>
          </div>

          <!-- Right: Official Circular Stamp & Authorized Signatory Block -->
          <div class="footer-right">
            <!-- Official Stamp sitting neatly directly above the signature line -->
            <div class="stamp-box">
              <svg viewBox="0 0 100 100" class="svg-stamp">
                <circle cx="50" cy="50" r="46" fill="none" stroke="#1f2d6b" stroke-width="1.8" stroke-dasharray="3,1.5" />
                <circle cx="50" cy="50" r="39" fill="none" stroke="#1f2d6b" stroke-width="1.2" />
                <circle cx="50" cy="50" r="23" fill="none" stroke="#1f2d6b" stroke-width="0.8" />
                <path id="stampTop" d="M 14,50 A 36,36 0 0,1 86,50" fill="none" />
                <path id="stampBottom" d="M 86,50 A 36,36 0 0,1 14,50" fill="none" />
                <text font-size="6.4" fill="#1f2d6b" font-weight="700" letter-spacing="1.2">
                  <textPath href="#stampTop" startOffset="50%" text-anchor="middle">UNICONVERGE TECHNOLOGIES</textPath>
                </text>
                <text font-size="6.4" fill="#1f2d6b" font-weight="700" letter-spacing="1.2">
                  <textPath href="#stampBottom" startOffset="50%" text-anchor="middle">PVT. LTD.</textPath>
                </text>
                <text x="50" y="53.5" font-size="7.5" font-weight="800" fill="#1f2d6b" text-anchor="middle">NOIDA</text>
              </svg>
            </div>

            <div class="sign-line"></div>
            <h3 class="signatory-name" id="display-signatory"><?= e($certificate['signatory']) ?></h3>
            <p class="signatory-sub" id="display-signatoryRole"><?= e($certificate['signatoryRole']) ?></p>
            <p class="signatory-sub" id="display-signatoryTitle"><?= e($certificate['signatoryTitle']) ?></p>
          </div>
        </footer>

      </div>
    </article>
  </div>

  <script>
    // Live editing & Drawer controls
    const toggleEditBtn = document.getElementById('toggleEditBtn');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');
    const drawer = document.getElementById('editorDrawer');
    const backdrop = document.getElementById('backdrop');

    function toggleDrawer(open) {
      if (!drawer || !backdrop) return;
      drawer.classList.toggle('open', open);
      backdrop.classList.toggle('open', open);
    }

    if (toggleEditBtn) toggleEditBtn.addEventListener('click', () => toggleDrawer(true));
    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', () => toggleDrawer(false));
    if (backdrop) backdrop.addEventListener('click', () => toggleDrawer(false));

    const fitToggleBtn = document.getElementById('fitToggleBtn');
    function updateFitScale() {
      if (document.body.classList.contains('actual-size')) {
        document.documentElement.style.setProperty('--cert-scale', '1');
        return;
      }
      const paddingX = window.innerWidth <= 760 ? 24 : 48;
      const paddingY = window.innerWidth <= 760 ? 24 : 48;
      const availW = Math.max(300, window.innerWidth - paddingX);
      const availH = Math.max(300, window.innerHeight - paddingY);
      const scale = Math.min(availW / 1024, availH / 700);
      document.documentElement.style.setProperty('--cert-scale', scale.toFixed(4));
    }

    if (fitToggleBtn) {
      fitToggleBtn.addEventListener('click', () => {
        const isActual = document.body.classList.toggle('actual-size');
        const textSpan = fitToggleBtn.querySelector('.btn-text');
        if (textSpan) textSpan.textContent = isActual ? 'Fit Screen' : '100% Size';
        updateFitScale();
      });
    }

    window.addEventListener('resize', updateFitScale);
    window.addEventListener('DOMContentLoaded', updateFitScale);
    updateFitScale();

    // Dynamic live sync between the editor and certificate preview
    const syncFields = [
      { inputId: 'input-recipient', displayId: 'display-recipient' },
      { inputId: 'input-duration', displayId: 'display-duration' },
      { inputId: 'input-program', displayId: 'display-program' },
      { inputId: 'input-dates', displayId: 'display-dates' },
      { inputId: 'input-certificationId', displayId: 'display-certificationId' },
      { inputId: 'input-issueDate', displayId: 'display-issueDate' },
      { inputId: 'input-signatory', displayId: 'display-signatory' },
      { inputId: 'input-signatoryRole', displayId: 'display-signatoryRole' },
      { inputId: 'input-signatoryTitle', displayId: 'display-signatoryTitle' }
    ];

    syncFields.forEach(({ inputId, displayId }) => {
      const input = document.getElementById(inputId);
      const display = document.getElementById(displayId);
      if (input && display) {
        input.addEventListener('input', (e) => {
          display.textContent = e.target.value;
        });
      }
    });

    const defaultData = <?= json_encode($defaultCertificate) ?>;
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        Object.keys(defaultData).forEach(key => {
          const input = document.getElementById('input-' + key);
          const display = document.getElementById('display-' + key);
          if (input) input.value = defaultData[key];
          if (display) display.textContent = defaultData[key];
        });
      });
    }
  </script>
</body>
</html>

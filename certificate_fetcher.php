<?php
/**
 * Certificate Data Fetcher & Helper Module
 * Provides unified data access for dashboard.php, index.php, and verify.php.
 */

// Published Google Sheet CSV export URL (replace with your published Google Sheet URL when ready)
$sheetCsvUrl = 'https://docs.google.com/spreadsheets/d/e/2PACX-xxxxxxx/pub?output=csv';

/**
 * Detects current application base URL dynamically (e.g. http://localhost/certificates)
 */
function getBaseUrl() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim($dir, '/');
    return $protocol . $host . $dir;
}

/**
 * Normalizes a raw associative row into standard certificate keys.
 */
function normalizeCertificateRow(array $row) {
    // Lookup with fallbacks for varying header styles
    $id = trim($row['Certification ID'] ?? $row['certificationId'] ?? $row['ID'] ?? $row['id'] ?? '');
    $recipient = trim($row['Full Name'] ?? $row['recipient'] ?? $row['Name'] ?? $row['name'] ?? '');
    $program = trim($row['Program Title'] ?? $row['program'] ?? $row['Program'] ?? '');
    $dates = trim($row['Dates'] ?? $row['dates'] ?? $row['Duration'] ?? '');
    $issueDate = trim($row['Issue Date'] ?? $row['issueDate'] ?? $row['Date'] ?? '');
    $signatory = trim($row['Signatory'] ?? $row['signatory'] ?? 'Kaushlendra Singh Sisodia');
    $signatoryTitle = trim($row['Signatory Title'] ?? $row['signatoryTitle'] ?? 'Chief Mentor, The IoT Academy');
    $signatoryRole = trim($row['Signatory Role'] ?? $row['signatoryRole'] ?? 'Director, Uniconverge Technologies');

    return [
        'certificationId' => $id,
        'Certification ID' => $id,
        'recipient'       => $recipient,
        'Full Name'       => $recipient,
        'program'         => $program,
        'Program Title'   => $program,
        'dates'           => $dates,
        'Dates'           => $dates,
        'issueDate'       => $issueDate,
        'Issue Date'      => $issueDate,
        'signatory'       => $signatory,
        'Signatory'       => $signatory,
        'signatoryTitle'  => $signatoryTitle,
        'Signatory Title' => $signatoryTitle,
        'signatoryRole'   => $signatoryRole,
        'Signatory Role'  => $signatoryRole,
    ];
}

/**
 * Fetches all certificates from Google Sheet CSV or local certificates.csv fallback.
 */
function getAllCertificates($sheetCsvUrl = null) {
    global $sheetCsvUrl;
    $url = $sheetCsvUrl ?? $GLOBALS['sheetCsvUrl'] ?? '';
    $rows = [];

    // 1. Try remote Google Sheet CSV if URL is real (not placeholder)
    $isPlaceholder = empty($url) || strpos($url, 'xxxxxxx') !== false;
    if (!$isPlaceholder) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 2.5,
                'follow_location' => 1,
                'user_agent' => 'PHP/CertificateFetcher'
            ]
        ]);
        $handle = @fopen($url, 'r', false, $context);
        if ($handle !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            if ($headers) {
                $headers = array_map('trim', $headers);
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if (count($data) === count($headers)) {
                        $rawRow = array_combine($headers, $data);
                        $rows[] = normalizeCertificateRow($rawRow);
                    }
                }
            }
            fclose($handle);
        }
    }

    // 2. Fall back to local certificates.csv if remote returned nothing
    if (empty($rows)) {
        $localCsv = __DIR__ . DIRECTORY_SEPARATOR . 'certificates.csv';
        if (file_exists($localCsv) && ($handle = fopen($localCsv, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            if ($headers) {
                $headers = array_map('trim', $headers);
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if (count($data) === count($headers)) {
                        $rawRow = array_combine($headers, $data);
                        $rows[] = normalizeCertificateRow($rawRow);
                    }
                }
            }
            fclose($handle);
        }
    }

    // 3. Fall back to embedded default dataset if still empty
    if (empty($rows)) {
        $sampleData = [
            [
                'Certification ID' => 'IOT-2026-001',
                'Full Name'       => 'Rahul Sharma',
                'Program Title'   => 'AI-Powered Embedded & IoT Program',
                'Dates'           => '01/01/2026 to 28/02/2026',
                'Issue Date'      => '05/03/2026',
                'Signatory'       => 'Kaushlendra Singh Sisodia',
                'Signatory Title' => 'Chief Mentor, The IoT Academy',
                'Signatory Role'  => 'Director, Uniconverge Technologies',
            ],
            [
                'Certification ID' => 'IOT-2026-002',
                'Full Name'       => 'Priya Patel',
                'Program Title'   => 'Advanced Robotics & Embedded Systems',
                'Dates'           => '15/01/2026 to 15/03/2026',
                'Issue Date'      => '20/03/2026',
                'Signatory'       => 'Kaushlendra Singh Sisodia',
                'Signatory Title' => 'Chief Mentor, The IoT Academy',
                'Signatory Role'  => 'Director, Uniconverge Technologies',
            ],
            [
                'Certification ID' => 'IOT-2026-003',
                'Full Name'       => 'Amit Verma',
                'Program Title'   => 'Industrial IoT & Cloud Computing',
                'Dates'           => '10/02/2026 to 10/04/2026',
                'Issue Date'      => '15/04/2026',
                'Signatory'       => 'Kaushlendra Singh Sisodia',
                'Signatory Title' => 'Chief Mentor, The IoT Academy',
                'Signatory Role'  => 'Director, Uniconverge Technologies',
            ]
        ];
        foreach ($sampleData as $item) {
            $rows[] = normalizeCertificateRow($item);
        }
    }

    return $rows;
}

/**
 * Looks up a single certificate by Certification ID.
 * Returns normalized array or null if not found.
 */
function getCertificateFromSheet($sheetCsvUrl, $id) {
    $id = trim((string)$id);
    if ($id === '') {
        return null;
    }

    $all = getAllCertificates($sheetCsvUrl);
    foreach ($all as $cert) {
        if (strcasecmp($cert['certificationId'], $id) === 0) {
            return $cert;
        }
    }

    return null;
}

/**
 * Writes an array of normalized certificates to the local certificates.csv file.
 */
function writeAllCertificatesToCsv(array $certificates) {
    $localCsv = __DIR__ . DIRECTORY_SEPARATOR . 'certificates.csv';
    $headers = [
        'Certification ID',
        'Full Name',
        'Program Title',
        'Dates',
        'Issue Date',
        'Signatory',
        'Signatory Title',
        'Signatory Role'
    ];

    $fp = @fopen($localCsv, 'w');
    if (!$fp) {
        return ['success' => false, 'message' => 'Unable to write to certificates.csv. Please verify directory permissions.'];
    }

    fputcsv($fp, $headers);
    foreach ($certificates as $cert) {
        $c = normalizeCertificateRow($cert);
        fputcsv($fp, [
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
    fclose($fp);
    return ['success' => true];
}

/**
 * Adds a new certificate or updates an existing one in certificates.csv.
 */
function saveCertificateToCsv(array $data) {
    $normalized = normalizeCertificateRow($data);
    if (empty($normalized['certificationId'])) {
        return ['success' => false, 'message' => 'Certification ID is required.'];
    }
    if (empty($normalized['recipient'])) {
        return ['success' => false, 'message' => 'Recipient name is required.'];
    }

    $existing = getAllCertificates(null);
    $updated = false;

    foreach ($existing as &$row) {
        if (strcasecmp($row['certificationId'], $normalized['certificationId']) === 0) {
            $row = $normalized;
            $updated = true;
            break;
        }
    }
    unset($row);

    if (!$updated) {
        $existing[] = $normalized;
    }

    $writeResult = writeAllCertificatesToCsv($existing);
    if ($writeResult['success']) {
        return [
            'success' => true,
            'is_update' => $updated,
            'certificate' => $normalized
        ];
    }
    return $writeResult;
}

/**
 * Imports certificate rows from an uploaded CSV file.
 * $mode: 'append' (merges/updates existing IDs) or 'replace' (overwrites certificates.csv).
 */
function importCertificatesFromCsv($fileTmpPath, $mode = 'append') {
    if (!file_exists($fileTmpPath) || !is_readable($fileTmpPath)) {
        return ['success' => false, 'message' => 'Uploaded file could not be read.'];
    }

    $handle = @fopen($fileTmpPath, 'r');
    if (!$handle) {
        return ['success' => false, 'message' => 'Failed to open uploaded CSV file.'];
    }

    // Read header row
    $headers = fgetcsv($handle, 2000, ',');
    if (!$headers) {
        fclose($handle);
        return ['success' => false, 'message' => 'CSV file is empty or corrupted.'];
    }

    // Strip BOM or invisible control characters from headers
    $headers = array_map(function($h) {
        return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string)$h));
    }, $headers);

    $importedRows = [];
    while (($data = fgetcsv($handle, 2000, ',')) !== false) {
        // Skip completely empty lines
        if (count($data) === 1 && trim($data[0]) === '') {
            continue;
        }

        if (count($data) === count($headers)) {
            $raw = array_combine($headers, $data);
            $normalized = normalizeCertificateRow($raw);
            if (!empty($normalized['certificationId']) && !empty($normalized['recipient'])) {
                $importedRows[] = $normalized;
            }
        }
    }
    fclose($handle);

    if (empty($importedRows)) {
        return [
            'success' => false,
            'message' => 'No valid records found in the CSV. Make sure headers include "Certification ID" and "Full Name".'
        ];
    }

    if ($mode === 'replace') {
        $finalList = $importedRows;
    } else {
        // Append mode: merge into current records
        $finalList = getAllCertificates(null);
        foreach ($importedRows as $newCert) {
            $matched = false;
            foreach ($finalList as &$existing) {
                if (strcasecmp($existing['certificationId'], $newCert['certificationId']) === 0) {
                    $existing = $newCert;
                    $matched = true;
                    break;
                }
            }
            unset($existing);
            if (!$matched) {
                $finalList[] = $newCert;
            }
        }
    }

    $res = writeAllCertificatesToCsv($finalList);
    if ($res['success']) {
        return [
            'success' => true,
            'count'   => count($importedRows),
            'total'   => count($finalList)
        ];
    }
    return $res;
}

/**
 * Deletes a certificate record by Certification ID.
 */
function deleteCertificateFromCsv($id) {
    $id = trim((string)$id);
    if (empty($id)) {
        return ['success' => false, 'message' => 'Invalid ID for deletion.'];
    }

    $existing = getAllCertificates(null);
    $filtered = [];
    $found = false;

    foreach ($existing as $cert) {
        if (strcasecmp($cert['certificationId'], $id) === 0) {
            $found = true;
        } else {
            $filtered[] = $cert;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'Certificate not found.'];
    }

    return writeAllCertificatesToCsv($filtered);
}


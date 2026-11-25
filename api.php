<?php
/**
 * Greenasia TMS v4.1 - Standalone PHP Web Server Backend
 * Pengganti Google Apps Script. Cukup upload bersama index.html ke web server (cPanel / Apache / Nginx).
 * Data tersimpan otomatis di folder ./data/tms_db.json (terproteksi .htaccess).
 */

declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$DATA_DIR = __DIR__ . '/data';
$DB_FILE = $DATA_DIR . '/tms_db.json';
$SESS_FILE = $DATA_DIR . '/sessions.json';

function jsonOut(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function hashPw(string $pw): string {
    return hash('sha256', 'gtr|' . $pw);
}

function ensureDataDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            jsonOut(['ok' => false, 'error' => 'Gagal membuat folder data/. Pastikan permission folder 755.'], 500);
        }
    }
    $hta = $dir . '/.htaccess';
    if (!file_exists($hta)) {
        @file_put_contents($hta, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
    }
    $idx = $dir . '/index.html';
    if (!file_exists($idx)) {
        @file_put_contents($idx, '');
    }
}

function defaultSeed(): array {
    $td = date('Y-m-d');
    $ts = date('d/m/Y H:i:s') . ' WIB';
    $addDays = function(int $days) use ($td): string {
        return date('Y-m-d', strtotime($td . " {$days} days"));
    };

    $P = 'Terminal JICT, Tanjung Priok';
    $D = 'Depo Marunda Empty';

    $s1 = 'SPK-' . date('ym') . '-001';
    $s2 = 'SPK-' . date('ym') . '-002';
    $s3 = 'SPK-' . date('ym') . '-003';

    $master = [
        'routes' => [
            ['jenis' => 'Import', 'term' => $P, 't' => 'Kawasan Industri Cikarang', 'depo' => $D, 'c' => [2800000, 3600000, 0, 0], 'u' => [900000, 1100000, 0, 0]],
            ['jenis' => 'Export', 'term' => $P, 't' => 'Kawasan Industri Cikarang', 'depo' => $D, 'c' => [2700000, 3500000, 0, 0], 'u' => [850000, 1050000, 0, 0]],
            ['jenis' => 'Repo', 'repoDir' => 'T2D', 'term' => $P, 't' => 'Repo Container', 'depo' => $D, 'c' => [950000, 1200000, 0, 0], 'u' => [300000, 350000, 0, 0]],
            ['jenis' => 'Repo', 'repoDir' => 'D2T', 'term' => $P, 't' => 'Repo Container', 'depo' => $D, 'c' => [950000, 1200000, 0, 0], 'u' => [300000, 350000, 0, 0]],
        ],
        'custs' => ['PT. Maju Jaya Logistik', 'CV. Sumber Makmur'],
        'custList' => [
            ['n' => 'PT. Maju Jaya Logistik', 'al' => 'Kawasan Industri Jababeka Tahap II Blok FF, Cikarang'],
            ['n' => 'CV. Sumber Makmur', 'al' => 'Jl. Raya Narogong KM 18, Cileungsi, Bogor'],
        ],
        'drivers' => [
            ['id' => 'D1', 'n' => 'Slamet Riyadi', 'hp' => '0812-3300-111', 'plat' => 'B 9123 UA', 'us' => 'slamet', 'pw' => hashPw('driver123')],
            ['id' => 'D2', 'n' => 'Budi Santoso', 'hp' => '0813-5500-222', 'plat' => 'B 9456 UB', 'us' => 'budi', 'pw' => hashPw('driver123')],
            ['id' => 'D3', 'n' => 'Agus Wijaya', 'hp' => '0857-1100-333', 'plat' => 'B 8821 UC', 'us' => 'agus', 'pw' => hashPw('driver123')],
        ],
        'trucks' => [
            ['plat' => 'B 9123 UA', 'jenis' => 'Head + Trailer 40ft', 'stnk' => 'STNK-9123-JKT', 'pajakDue' => $addDays(140), 'stnkDue' => $addDays(800)],
            ['plat' => 'B 9456 UB', 'jenis' => 'Head + Trailer 20ft', 'stnk' => 'STNK-9456-JKT', 'pajakDue' => $addDays(12), 'stnkDue' => $addDays(420)],
            ['plat' => 'B 8821 UC', 'jenis' => 'Head + Trailer 40ft', 'stnk' => 'STNK-8821-JKT', 'pajakDue' => $addDays(-3), 'stnkDue' => $addDays(18)],
        ],
        'users' => [
            ['us' => 'superadmin', 'pw' => hashPw('super123'), 'role' => 'superadmin', 'n' => 'Super Admin'],
            ['us' => 'admin', 'pw' => hashPw('admin123'), 'role' => 'admin', 'n' => 'Admin Ops'],
            ['us' => 'kasir', 'pw' => hashPw('kasir123'), 'role' => 'kasir', 'n' => 'ENI'],
        ],
        'set' => [
            'ppn' => 11, 'pph' => 2, 'term' => 14,
            'cicilanTruk' => 18000000,
            'jmlSupir' => 6,
            'gajiSupirSatuan' => 1000000,
            'gajiMontir' => 6000000,
        ],
        'kasbon' => (object)[],
        'expenses' => [
            ['id' => 'EX-1', 'tgl' => $td, 'waktu' => $ts, 'trk' => 'B 8821 UC', 'kat' => 'Perbaikan / Servis', 'ket' => 'Ganti selang angin rem trailer & kampas', 'nom' => 650000, 'oleh' => 'Admin Ops'],
        ],
        'topups' => [
            ['id' => 'TOP-1', 'tgl' => $td, 'waktu' => $ts, 'nom' => 15000000, 'ket' => 'Top-up saldo operasional harian token Admin', 'oleh' => 'ENI (Kasir)'],
        ],
        'rekonNotes' => (object)[],
    ];

    $orders = [
        $s1 => [
            'spk' => $s1, 'jenis' => 'Import', 'tgl' => $td, 'createdAt' => $ts, 'dispatchedAt' => $ts, 'acceptedAt' => $ts,
            'cust' => 'PT. Maju Jaya Logistik',
            'srcOrder' => ['perusahaan' => 'PT. Samudra Forwarding', 'nama' => 'Rina Kusuma', 'telp' => '0811-8890-441'],
            'a' => $P, 'term' => $P, 't' => 'Kawasan Industri Cikarang', 'depo' => $D,
            'tipe' => 'Trailer 40ft', 'cont' => 'MRKU 4829102', 'seal' => 'SL-99281', 'ton' => '22',
            'cat' => 'Bongkar di Gudang B', 'st' => 'Dispatch',
            'items' => [['n' => 'Ongkos angkut [Import] Trailer 40ft', 'q' => 1, 'p' => 3600000]],
            'discType' => 'rp', 'discVal' => 0, 'komisi' => 100000, 'ppn' => true, 'pph' => false,
            'uj' => 1100000, 'drv' => 'D1', 'trk' => 'B 9123 UA', 'accepted' => true,
            'docs' => ['term' => ['lokasi' => $P, 'jam' => '08:00 WIB', 'ref' => 'DO-8812'], 'depo' => ['lokasi' => $D, 'jam' => '17:00 WIB', 'ref' => 'EIR-221']],
            'gatepassDoc' => null, 'photos' => (object)[], 'drvCond' => 'Lancar', 'drvReport' => '', 'pod' => '', 'tiba' => '', 'drvStep' => '', 'seen' => true,
        ],
        $s2 => [
            'spk' => $s2, 'jenis' => 'Repo', 'repoDir' => 'D2T', 'tgl' => $td, 'createdAt' => $ts, 'dispatchedAt' => $ts,
            'cust' => 'PT. Maju Jaya Logistik',
            'srcOrder' => ['perusahaan' => 'PT. Samudra Forwarding', 'nama' => 'Rina Kusuma', 'telp' => '0811-8890-441'],
            'a' => $D, 'term' => $P, 't' => 'Repo Container', 'depo' => $D,
            'tipe' => 'Trailer 40ft', 'cont' => 'MSCU 7731904', 'seal' => '-', 'ton' => '0',
            'cat' => 'Repo empty setelah bongkar SPK-001', 'st' => 'Dispatch',
            'items' => [['n' => 'Ongkos angkut [Repo] Trailer 40ft', 'q' => 1, 'p' => 1200000]],
            'discType' => 'rp', 'discVal' => 0, 'komisi' => 0, 'ppn' => true, 'pph' => false,
            'uj' => 350000, 'drv' => 'D1', 'trk' => 'B 9123 UA', 'accepted' => false,
            'docs' => ['term' => ['lokasi' => $P], 'depo' => ['lokasi' => $D]],
            'gatepassDoc' => null, 'photos' => (object)[], 'drvCond' => 'Lancar', 'drvReport' => '', 'pod' => '', 'tiba' => '', 'drvStep' => '', 'seen' => false,
        ],
        $s3 => [
            'spk' => $s3, 'jenis' => 'Export', 'tgl' => $td, 'createdAt' => $ts,
            'cust' => 'CV. Sumber Makmur',
            'srcOrder' => ['perusahaan' => 'CV. Sumber Makmur', 'nama' => 'Hendra Wijaya', 'telp' => '0813-7741-900'],
            'a' => $D, 'term' => $P, 't' => 'Kawasan Industri Cikarang', 'depo' => $D,
            'tipe' => 'Trailer 40ft', 'cont' => 'CMAU 1120944', 'seal' => 'SL-55102', 'ton' => '20',
            'cat' => 'Closing kapal jam 22:00 WIB', 'st' => 'Selesai',
            'items' => [['n' => 'Ongkos angkut [Export] Trailer 40ft', 'q' => 1, 'p' => 3500000]],
            'discType' => 'rp', 'discVal' => 0, 'komisi' => 0, 'ppn' => true, 'pph' => false,
            'uj' => 1050000, 'drv' => 'D2', 'trk' => 'B 9456 UB', 'accepted' => true, 'acceptedAt' => $ts,
            'docs' => ['term' => ['lokasi' => $P], 'depo' => ['lokasi' => $D]],
            'gatepassDoc' => null, 'photos' => (object)[],
            'drvCond' => 'Antre Panjang di Terminal / Depo',
            'drvReport' => 'Antrean masuk gate JICT sekitar 2,5 jam karena sistem gate sempat offline, namun muatan aman dan masuk sebelum closing.',
            'pod' => 'SJ-EXP-889', 'tiba' => $td, 'completedAt' => $ts, 'finishedAt' => $ts, 'drvStep' => '', 'seen' => true,
        ],
    ];

    return [
        'master' => $master,
        'orders' => $orders,
        'invoices' => [],
    ];
}

function loadJsonFile(string $path, callable $defaultFn): array {
    if (!file_exists($path)) {
        $init = $defaultFn();
        saveJsonFile($path, $init);
        return $init;
    }
    $fp = fopen($path, 'r');
    if (!$fp) return $defaultFn();
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : $defaultFn();
}

function saveJsonFile(string $path, array $data): void {
    $fp = fopen($path, 'c+');
    if (!$fp) jsonOut(['ok' => false, 'error' => 'Gagal menulis database server.'], 500);
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function getBearerToken(): string {
    $tok = '';
    if (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
        $tok = trim($_SERVER['HTTP_X_API_TOKEN']);
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
            $tok = $m[1];
        }
    } elseif (function_exists('apache_request_headers')) {
        $hdrs = apache_request_headers();
        foreach ($hdrs as $k => $v) {
            if (strtolower($k) === 'x-api-token') $tok = trim($v);
            if (strtolower($k) === 'authorization' && preg_match('/Bearer\s+(\S+)/i', $v, $m)) $tok = $m[1];
        }
    }
    return $tok;
}

function getSessionUser(string $sessFile): ?array {
    $tok = getBearerToken();
    if (!$tok) return null;
    $sessions = loadJsonFile($sessFile, fn() => []);
    if (!isset($sessions[$tok])) return null;
    $s = $sessions[$tok];
    if (($s['exp'] ?? 0) < time()) {
        unset($sessions[$tok]);
        saveJsonFile($sessFile, $sessions);
        return null;
    }
    return $s['user'] ?? null;
}

function requireAuth(string $sessFile): array {
    $user = getSessionUser($sessFile);
    if (!$user) jsonOut(['ok' => false, 'error' => 'Sesi berakhir. Silakan login kembali.'], 401);
    return $user;
}

ensureDataDir($DATA_DIR);

$action = $_GET['action'] ?? '';
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '{}', true);
if (!is_array($payload)) $payload = [];

if ($action === 'health') {
    loadJsonFile($DB_FILE, fn() => defaultSeed());
    jsonOut(['ok' => true, 'mode' => 'php-webserver', 'time' => date('d/m/Y H:i:s') . ' WIB']);
}

if ($action === 'login') {
    $u = strtolower(trim((string)($payload['username'] ?? '')));
    $pw = (string)($payload['password'] ?? '');
    if (!$u || !$pw) jsonOut(['ok' => false, 'error' => 'Username dan password wajib diisi.'], 400);

    $db = loadJsonFile($DB_FILE, fn() => defaultSeed());
    $h = hashPw($pw);
    $found = null;

    foreach ($db['master']['users'] ?? [] as $usr) {
        if (strtolower($usr['us'] ?? '') === $u && ($usr['pw'] ?? '') === $h) {
            $found = ['role' => $usr['role'], 'us' => $usr['us'], 'n' => $usr['n']];
            break;
        }
    }
    if (!$found) {
        foreach ($db['master']['drivers'] ?? [] as $drv) {
            if (strtolower($drv['us'] ?? '') === $u && ($drv['pw'] ?? '') === $h) {
                $found = ['role' => 'driver', 'us' => $drv['us'], 'id' => $drv['id'], 'n' => $drv['n']];
                break;
            }
        }
    }

    if (!$found) {
        jsonOut(['ok' => false, 'error' => 'Username atau password salah.'], 401);
    }

    $tok = bin2hex(random_bytes(32));
    $sessions = loadJsonFile($SESS_FILE, fn() => []);
    // Bersihkan sesi kedaluwarsa
    $now = time();
    foreach ($sessions as $k => $v) {
        if (($v['exp'] ?? 0) < $now) unset($sessions[$k]);
    }
    $sessions[$tok] = ['user' => $found, 'exp' => $now + 86400];
    saveJsonFile($SESS_FILE, $sessions);

    jsonOut(['ok' => true, 'token' => $tok, 'user' => $found]);
}

if ($action === 'logout') {
    $tok = getBearerToken();
    if ($tok) {
        $sessions = loadJsonFile($SESS_FILE, fn() => []);
        unset($sessions[$tok]);
        saveJsonFile($SESS_FILE, $sessions);
    }
    jsonOut(['ok' => true]);
}

if ($action === 'bootstrap') {
    $user = requireAuth($SESS_FILE);
    $db = loadJsonFile($DB_FILE, fn() => defaultSeed());
    jsonOut([
        'ok' => true,
        'user' => $user,
        'master' => $db['master'],
        'orders' => empty($db['orders']) ? (object)[] : $db['orders'],
        'invoices' => empty($db['invoices']) ? (object)[] : $db['invoices'],
    ]);
}

if ($action === 'saveMaster') {
    $user = requireAuth($SESS_FILE);
    if ($user['role'] === 'driver') {
        jsonOut(['ok' => false, 'error' => 'Akses ditolak.'], 403);
    }
    $db = loadJsonFile($DB_FILE, fn() => defaultSeed());
    $db['master'] = $payload;
    saveJsonFile($DB_FILE, $db);
    jsonOut(['ok' => true]);
}

if ($action === 'saveOrder') {
    $user = requireAuth($SESS_FILE);
    $spk = trim((string)($payload['spk'] ?? ''));
    if (!$spk) jsonOut(['ok' => false, 'error' => 'Nomor SPK tidak valid.'], 400);

    // Serialize order writes so two simultaneous dispatches cannot both pass the overlap check.
    $writeLock = fopen($DB_FILE . '.write.lock', 'c');
    if (!$writeLock || !flock($writeLock, LOCK_EX)) {
        if (is_resource($writeLock)) fclose($writeLock);
        jsonOut(['ok' => false, 'error' => 'Tidak dapat mengunci penyimpanan order. Coba kembali.'], 500);
    }

    $db = loadJsonFile($DB_FILE, fn() => defaultSeed());
    if (!is_array($db['orders'])) $db['orders'] = [];

    if (($user['role'] ?? '') === 'driver') {
        $existing = $db['orders'][$spk] ?? null;
        if (!is_array($existing)) jsonOut(['ok' => false, 'error' => 'Order tidak ditemukan.'], 404);
        if (($existing['drv'] ?? '') !== ($user['id'] ?? '')) {
            jsonOut(['ok' => false, 'error' => 'Order ini tidak ditugaskan kepada Anda.'], 403);
        }
        if (($existing['st'] ?? '') !== 'Dispatch') {
            jsonOut(['ok' => false, 'error' => 'Order tidak lagi aktif untuk driver.'], 409);
        }

        // Driver may update only operational fields; assignment and booking data remain server-owned.
        $allowed = ['photos', 'drvStep', 'drvCond', 'drvReport', 'pod', 'tiba', 'completedAt', 'finishedAt', 'seen'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $payload)) {
                $existing[$field] = $payload[$field];
            }
        }

        if (($existing['accepted'] ?? false) !== true) {
            $existing['accepted'] = true;
            $existing['acceptedAt'] = date('d/m/Y H:i:s') . ' WIB';
        }

        if (($payload['st'] ?? '') === 'Selesai') {
            $pod = $existing['photos']['pod'] ?? '';
            if (!is_string($pod) || $pod === '') {
                jsonOut(['ok' => false, 'error' => 'Foto POD wajib tersimpan sebelum order diselesaikan.'], 400);
            }
            if (($existing['drvCond'] ?? 'Lancar') !== 'Lancar' && trim((string)($existing['drvReport'] ?? '')) === '') {
                jsonOut(['ok' => false, 'error' => 'Report kendala wajib diisi sebelum order diselesaikan.'], 400);
            }
            $existing['st'] = 'Selesai';
        }

        $existing['seen'] = true;
        $db['orders'][$spk] = $existing;
    } else {
        $current = $db['orders'][$spk] ?? null;
        $isDispatch = (($payload['st'] ?? '') === 'Dispatch');
        $isDispatching = $isDispatch && (!is_array($current) || ($current['st'] ?? '') !== 'Dispatch');
        if ($isDispatch) {
            if (empty($payload['drv']) || empty($payload['trk'])) {
                jsonOut(['ok' => false, 'error' => 'SPK Dispatch wajib memiliki driver dan unit.'], 400);
            }

            $startRaw = (string)($payload['planStart'] ?? '');
            $endRaw = (string)($payload['planEnd'] ?? '');
            $validStart = preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $startRaw) === 1;
            $validEnd = preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $endRaw) === 1;
            $startTime = $validStart ? DateTime::createFromFormat('!H:i', $startRaw) : false;
            $endTime = $validEnd ? DateTime::createFromFormat('!H:i', $endRaw) : false;

            $sameLegacySchedule = is_array($current)
                && ($current['st'] ?? '') === 'Dispatch'
                && ($current['drv'] ?? '') === ($payload['drv'] ?? '')
                && ($current['trk'] ?? '') === ($payload['trk'] ?? '')
                && ($current['tgl'] ?? '') === ($payload['tgl'] ?? '')
                && ($current['planStart'] ?? '') === $startRaw
                && ($current['planEnd'] ?? '') === $endRaw;

            if (!$startTime || !$endTime) {
                if (!$sameLegacySchedule) {
                    jsonOut(['ok' => false, 'error' => 'SPK Dispatch wajib memiliki jam rencana valid. Lengkapi jam mulai dan selesai.'], 400);
                }
            } else {
                $startMinute = ((int)$startTime->format('H')) * 60 + (int)$startTime->format('i');
                $endMinute = ((int)$endTime->format('H')) * 60 + (int)$endTime->format('i');
                if ($endMinute <= $startMinute) {
                    jsonOut(['ok' => false, 'error' => 'Jam selesai harus setelah jam mulai pada tanggal muat yang sama.'], 400);
                }

                $isLegacyScheduleRepair = is_array($current)
                    && ($current['st'] ?? '') === 'Dispatch'
                    && ($current['drv'] ?? '') === ($payload['drv'] ?? '')
                    && ($current['trk'] ?? '') === ($payload['trk'] ?? '')
                    && ($current['tgl'] ?? '') === ($payload['tgl'] ?? '')
                    && (empty($current['planStart']) || empty($current['planEnd']));

                foreach ($db['orders'] as $otherSpk => $other) {
                    if ($otherSpk === $spk || !is_array($other) || ($other['st'] ?? '') !== 'Dispatch' || ($other['tgl'] ?? '') !== ($payload['tgl'] ?? '')) continue;
                    if (($other['drv'] ?? '') !== ($payload['drv'] ?? '') && ($other['trk'] ?? '') !== ($payload['trk'] ?? '')) continue;
                    $otherStartRaw = (string)($other['planStart'] ?? '');
                    $otherEndRaw = (string)($other['planEnd'] ?? '');
                    $otherStartValid = preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $otherStartRaw) === 1;
                    $otherEndValid = preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $otherEndRaw) === 1;
                    $otherStart = $otherStartValid ? DateTime::createFromFormat('!H:i', $otherStartRaw) : false;
                    $otherEnd = $otherEndValid ? DateTime::createFromFormat('!H:i', $otherEndRaw) : false;
                    if (!$otherStart || !$otherEnd) {
                        if ($isLegacyScheduleRepair) continue;
                        jsonOut(['ok' => false, 'error' => 'Ada SPK aktif pada tanggal ini tanpa jadwal terperinci. Lengkapi jadwal SPK tersebut terlebih dahulu.'], 409);
                    }
                    $os = ((int)$otherStart->format('H')) * 60 + (int)$otherStart->format('i');
                    $oe = ((int)$otherEnd->format('H')) * 60 + (int)$otherEnd->format('i');
                    if ($startMinute < $oe && $os < $endMinute) {
                        jsonOut(['ok' => false, 'error' => 'Jadwal bentrok dengan SPK ' . $otherSpk . '.'], 409);
                    }
                }
            }
        }
        $db['orders'][$spk] = $payload;
    }

    saveJsonFile($DB_FILE, $db);
    flock($writeLock, LOCK_UN);
    fclose($writeLock);
    jsonOut(['ok' => true]);
}

if ($action === 'deleteOrder') {
    $user = requireAuth($SESS_FILE);
    if ($user['role'] !== 'superadmin') {
        jsonOut(['ok' => false, 'error' => 'Hanya Superadmin yang dapat menghapus order.'], 403);
    }
    $spk = trim((string)($payload['spk'] ?? ''));
    $db = loadJsonFile($DB_FILE, fn() => defaultSeed());
    if (isset($db['orders'][$spk])) {
        unset($db['orders'][$spk]);
        saveJsonFile($DB_FILE, $db);
    }
    jsonOut(['ok' => true]);
}

if ($action === 'saveInvoice') {
    $user = requireAuth($SESS_FILE);
    if (!in_array($user['role'], ['kasir', 'superadmin'], true)) {
        jsonOut(['ok' => false, 'error' => 'Akses ditolak.'], 403);
    }
    $id = trim((string)($payload['id'] ?? ''));
    if (!$id) jsonOut(['ok' => false, 'error' => 'ID invoice tidak valid.'], 400);
    $db = loadJsonFile($DB_FILE, fn() => defaultSeed());
    if (!is_array($db['invoices'])) $db['invoices'] = [];
    $db['invoices'][$id] = $payload;
    saveJsonFile($DB_FILE, $db);
    jsonOut(['ok' => true]);
}

jsonOut(['ok' => false, 'error' => 'Action tidak dikenali.'], 404);

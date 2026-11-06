<?php
/**
 * Pemeriksa kesiapan hosting Guardian Digital (InfinityFree).
 * Letakkan di folder public, buka: https://DOMAIN-ANDA/cek-hosting.php?kunci=ISI-KUNCI-DI-BAWAH
 * HAPUS file ini setelah semua baris berwarna hijau.
 */
$KUNCI = 'ganti-dengan-kata-acak-anda';

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');

if ($KUNCI === 'ganti-dengan-kata-acak-anda' || ! isset($_GET['kunci']) || ! hash_equals($KUNCI, (string) $_GET['kunci'])) {
    http_response_code(403);
    exit('Ubah nilai $KUNCI di file ini, lalu buka dengan ?kunci=NILAI-ITU');
}

$base = dirname(__DIR__);
$rows = array();

function cek($label, $ok, $detail = '')
{
    global $rows;
    $rows[] = array($label, $ok, $detail);
}

function baca_env($file)
{
    $out = array();
    if (! is_file($file)) {
        return $out;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
            $q = $v[0];
            $end = strrpos($v, $q);
            $v = $end > 0 ? substr($v, 1, $end - 1) : substr($v, 1);
        } else {
            $pos = strpos($v, ' #');
            if ($pos !== false) {
                $v = trim(substr($v, 0, $pos));
            }
        }
        $out[$k] = $v;
    }

    return $out;
}

// 1. PHP
cek('Versi PHP 8.3 atau lebih baru', version_compare(PHP_VERSION, '8.3.0', '>='), 'Terdeteksi ' . PHP_VERSION);

$ext = array('ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'zlib');
$hilang = array();
foreach ($ext as $e) {
    if (! extension_loaded($e)) {
        $hilang[] = $e;
    }
}
cek('Ekstensi PHP yang dibutuhkan Laravel + backup (zlib)', count($hilang) === 0, count($hilang) ? 'Hilang: ' . implode(', ', $hilang) : 'Lengkap');

// 2. Berkas aplikasi
cek('Folder vendor ada (vendor/autoload.php)', is_file($base . '/vendor/autoload.php'));
cek('Inti Laravel terunggah lengkap', is_file($base . '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php'));
cek('bootstrap/app.php ada', is_file($base . '/bootstrap/app.php'));
cek('public/build/manifest.json ada (hasil npm run build)', is_file(__DIR__ . '/build/manifest.json') || is_file(__DIR__ . '/build/.vite/manifest.json'));
cek('public/.htaccess ada', is_file(__DIR__ . '/.htaccess'));
cek('.htaccess di folder utama (htdocs) ada', is_file($base . '/.htaccess'));

$cm = $base . '/vendor/composer/autoload_classmap.php';
cek('Autoloader tidak melebihi 1 MB (batas InfinityFree untuk file PHP)', ! is_file($cm) || filesize($cm) < 1000000, is_file($cm) ? round(filesize($cm) / 1024) . ' KB' : 'tanpa classmap');

$pk = $base . '/bootstrap/cache/packages.php';
$sisa = is_file($pk) && (strpos(file_get_contents($pk), 'laravel/pail') !== false || strpos(file_get_contents($pk), 'nunomaduro/collision') !== false);
cek('bootstrap/cache bersih dari paket dev (pail/collision)', ! $sisa, $sisa ? 'Hapus isi folder bootstrap/cache lewat FTP' : 'Bersih');

// 3. Izin tulis
foreach (array('storage/logs', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/app/private', 'bootstrap/cache') as $d) {
    $p = $base . '/' . $d;
    cek('Folder bisa ditulis: ' . $d, is_dir($p) && is_writable($p), is_dir($p) ? '' : 'Folder belum ada, buat lewat FTP');
}
$bk = $base . '/storage/app/private/backups';
if (! is_dir($bk)) {
    @mkdir($bk, 0755, true);
}
cek('Folder backup bisa ditulis: storage/app/private/backups', is_dir($bk) && is_writable($bk));

// 4. .env
$env = baca_env($base . '/.env');
cek('.env ada dan terbaca', count($env) > 0);
cek('APP_ENV=production', isset($env['APP_ENV']) && $env['APP_ENV'] === 'production', isset($env['APP_ENV']) ? $env['APP_ENV'] : 'kosong');
cek('APP_DEBUG=false', isset($env['APP_DEBUG']) && strtolower($env['APP_DEBUG']) === 'false');
cek('APP_KEY terisi', ! empty($env['APP_KEY']) && strpos($env['APP_KEY'], 'base64:') === 0);
cek('APP_URL memakai https', ! empty($env['APP_URL']) && strpos($env['APP_URL'], 'https://') === 0, isset($env['APP_URL']) ? $env['APP_URL'] : 'kosong');
cek('Sesi dan cache di database', isset($env['SESSION_DRIVER'], $env['CACHE_STORE']) && $env['SESSION_DRIVER'] === 'database' && $env['CACHE_STORE'] === 'database');
cek('Tidak ada MYSQL_ATTR_SSL_CA (khusus Aiven, tidak dipakai di sini)', empty($env['MYSQL_ATTR_SSL_CA']));
cek('LAZY_SCHEDULER=true (pengganti cron untuk backup otomatis)', isset($env['LAZY_SCHEDULER']) && strtolower($env['LAZY_SCHEDULER']) === 'true');

// 5. Database
if (! empty($env['DB_HOST']) && ! empty($env['DB_DATABASE']) && extension_loaded('pdo_mysql')) {
    try {
        $port = ! empty($env['DB_PORT']) ? $env['DB_PORT'] : '3306';
        $pdo = new PDO(
            'mysql:host=' . $env['DB_HOST'] . ';port=' . $port . ';dbname=' . $env['DB_DATABASE'] . ';charset=utf8mb4',
            isset($env['DB_USERNAME']) ? $env['DB_USERNAME'] : '',
            isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8)
        );
        cek('Koneksi database berhasil', true, 'MySQL/MariaDB ' . $pdo->query('select version()')->fetchColumn());

        $tabel = array('users', 'sessions', 'cache', 'scenarios', 'lessons', 'badges', 'settings', 'activity_logs', 'migrations');
        $ada = $pdo->query('show tables')->fetchAll(PDO::FETCH_COLUMN);
        $kurang = array_diff($tabel, $ada);
        cek('Tabel utama sudah diimpor (' . count($ada) . ' tabel)', count($kurang) === 0, count($kurang) ? 'Belum ada: ' . implode(', ', $kurang) : '');

        if (in_array('users', $ada, true)) {
            $n = (int) $pdo->query("select count(*) from users where role = 'superadmin'")->fetchColumn();
            cek('Akun superadmin tersedia', $n > 0, $n . ' akun');
        }
        if (in_array('scenarios', $ada, true)) {
            $n = (int) $pdo->query('select count(*) from scenarios')->fetchColumn();
            cek('Data misi (skenario) terisi', $n > 0, $n . ' misi');
        }
    } catch (Throwable $e) {
        cek('Koneksi database berhasil', false, 'Gagal: periksa DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD di .env');
    }
} else {
    cek('Koneksi database berhasil', false, 'DB_HOST atau DB_DATABASE di .env kosong');
}

// 6. SMTP
if (isset($env['MAIL_MAILER']) && $env['MAIL_MAILER'] === 'smtp' && ! empty($env['MAIL_HOST'])) {
    $mp = ! empty($env['MAIL_PORT']) ? (int) $env['MAIL_PORT'] : 587;
    $prefix = $mp === 465 ? 'ssl://' : '';
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($prefix . $env['MAIL_HOST'], $mp, $errno, $errstr, 8);
    cek('Server email bisa dijangkau (' . $env['MAIL_HOST'] . ':' . $mp . ')', (bool) $fp, $fp ? '' : 'Gagal: ' . $errstr);
    if ($fp) {
        fclose($fp);
    }
    cek('MAIL_FROM_ADDRESS terisi', ! empty($env['MAIL_FROM_ADDRESS']), isset($env['MAIL_FROM_ADDRESS']) ? $env['MAIL_FROM_ADDRESS'] : '');
} else {
    cek('MAIL_MAILER=smtp (email nyata)', false, 'Masih "' . (isset($env['MAIL_MAILER']) ? $env['MAIL_MAILER'] : 'kosong') . '": email lupa kata sandi dan verifikasi tidak akan terkirim');
}

$semua = true;
foreach ($rows as $r) {
    if (! $r[1]) {
        $semua = false;
    }
}
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cek hosting Guardian Digital</title>
<style>
body{font-family:system-ui,Arial,sans-serif;max-width:860px;margin:24px auto;padding:0 16px;color:#1e293b}
table{border-collapse:collapse;width:100%}td{padding:8px 10px;border-bottom:1px solid #e2e8f0;vertical-align:top}
.ok{color:#15803d;font-weight:700}.no{color:#b91c1c;font-weight:700}.d{color:#64748b;font-size:.9em}
.box{padding:12px 16px;border-radius:8px;margin:16px 0}.g{background:#dcfce7}.r{background:#fee2e2}
</style></head><body>
<h1>Cek hosting Guardian Digital</h1>
<div class="box <?php echo $semua ? 'g' : 'r'; ?>">
<?php echo $semua ? 'Semua pemeriksaan lulus. Hapus file ini dari server sekarang.' : 'Ada yang belum lulus. Perbaiki baris merah, lalu muat ulang halaman ini.'; ?>
</div>
<table>
<?php foreach ($rows as $r) { ?>
<tr><td class="<?php echo $r[1] ? 'ok' : 'no'; ?>"><?php echo $r[1] ? 'LULUS' : 'GAGAL'; ?></td>
<td><?php echo htmlspecialchars($r[0]); ?><?php if ($r[2] !== '') { ?><div class="d"><?php echo htmlspecialchars($r[2]); ?></div><?php } ?></td></tr>
<?php } ?>
</table>
<p class="d">Waktu server: <?php echo htmlspecialchars(date('Y-m-d H:i:s T')); ?> (UTC). Aplikasi memakai zona Asia/Jakarta.</p>
</body></html>

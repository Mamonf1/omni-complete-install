<?php
/**
 * أومني — Setup
 * ينشئ حساب الأدمن مرة واحدة ثم يحذف نفسه
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

foreach ([__DIR__.'/../.env', __DIR__.'/.env', dirname(__DIR__, 2).'/.env'] as $p) {
    if (is_readable($p)) {
        foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            putenv(trim($k).'='.trim($v));
        }
        break;
    }
}

function env($k, $d = null) {
    $v = getenv($k);
    return ($v !== false && $v !== '') ? $v : $d;
}

$ADMIN_PHONE = '0930306875';
$ADMIN_EMAIL = env('OMNI_ADMIN_EMAIL', 'omniamarkeet@gmail.com');
$ADMIN_NAME = 'مدير النظام';
$ADMIN_PASSWORD = 'M22558800';
$SETUP_TOKEN = 'omni_setup_2026';

if (!isset($_GET['t']) || $_GET['t'] !== $SETUP_TOKEN) {
    http_response_code(403);
    die('<div style="font-family:monospace;background:#111;color:#f66;padding:40px;text-align:center;min-height:100vh;"><h1>403 — Forbidden</h1><p>أضف ?t=TOKEN في الرابط</p></div>');
}

try {
    $pdo = new PDO(
        'mysql:host=' . env('OMNI_DB_HOST') . ';port=' . env('OMNI_DB_PORT', 3306) . ';dbname=' . env('OMNI_DB_NAME') . ';charset=utf8mb4',
        env('OMNI_DB_USER'),
        env('OMNI_DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('❌ فشل الاتصال بقاعدة البيانات: ' . htmlspecialchars($e->getMessage()));
}

function normalize_phone_num($p) {
    $p = preg_replace('/[^0-9+]/', '', $p);
    $p = str_replace('+', '', $p);
    if (substr($p,0,2) === '00') $p = substr($p,2);
    if (substr($p,0,1) === '0') $p = '963'.substr($p,1);
    if (substr($p,0,3) !== '963' && (strlen($p)===9 || strlen($p)===10)) $p = '963'.$p;
    return $p;
}

function make_uuid() {
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    $h = bin2hex($b);
    return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20,12);
}

function make_account_number($pdo) {
    for ($i = 0; $i < 20; $i++) {
        $n = str_pad((string)random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('SELECT id FROM profiles WHERE account_number = ?');
        $stmt->execute([$n]);
        if (!$stmt->fetch()) return $n;
    }
    throw new Exception('فشل توليد رقم حساب');
}

$np = normalize_phone_num($ADMIN_PHONE);
$hash = password_hash($ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 10]);

$stmt = $pdo->prepare('SELECT * FROM profiles WHERE phone = ? OR email = ? LIMIT 1');
$stmt->execute([$np, strtolower($ADMIN_EMAIL)]);
$existing = $stmt->fetch();

echo '<div style="font-family:Cairo,sans-serif;background:#0F172A;color:#fff;padding:30px;min-height:100vh;direction:rtl;">';
echo '<div style="max-width:700px;margin:0 auto;">';

if ($existing) {
    $pdo->prepare('UPDATE profiles SET password_hash = ?, role = "admin", email = ?, name = ?, merchant_status = "approved", user_type = "regular" WHERE id = ?')->execute([$hash, $ADMIN_EMAIL, $ADMIN_NAME, $existing['id']]);
    echo '<h1 style="color:#FCD34D;">✅ تم تحديث حساب الأدمن</h1>';
    echo '<p><strong>الهاتف:</strong> +' . htmlspecialchars($np) . '</p>';
    echo '<p><strong>البريد:</strong> ' . htmlspecialchars($ADMIN_EMAIL) . '</p>';
    echo '<p><strong>رقم الحساب:</strong> ' . htmlspecialchars($existing['account_number']) . '</p>';
} else {
    $id = make_uuid();
    $acc = make_account_number($pdo);
    $pdo->prepare('INSERT INTO profiles (id, name, phone, phone_country, email, password_hash, role, account_number, balance_omni, balance_syp, merchant_status, user_type, spin_balance) VALUES (?, ?, ?, "963", ?, ?, "admin", ?, 0, 0, "approved", "regular", 0)')->execute([$id, $ADMIN_NAME, $np, $ADMIN_EMAIL, $hash, $acc]);
    echo '<h1 style="color:#FCD34D;">✅ تم إنشاء حساب الأدمن</h1>';
    echo '<p><strong>الهاتف:</strong> +' . htmlspecialchars($np) . '</p>';
    echo '<p><strong>البريد:</strong> ' . htmlspecialchars($ADMIN_EMAIL) . '</p>';
    echo '<p><strong>رقم الحساب:</strong> ' . htmlspecialchars($acc) . '</p>';
}

echo '<hr style="margin:20px 0;border-color:#333;">';
echo '<p style="color:#cbd5e1;">اسم المستخدم: <strong>' . htmlspecialchars($ADMIN_EMAIL) . '</strong></p>';
echo '<p style="color:#cbd5e1;">كلمة المرور: <strong>' . htmlspecialchars($ADMIN_PASSWORD) . '</strong></p>';
echo '<p style="color:#fca5a5;">⚠️ احذف هذا الملف مباشرة بعد تسجيل الدخول.</p>';
echo '</div></div>';

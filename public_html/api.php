<?php
/**
 * أومني API — v6
 * يشتغل مع MySQL وJWT
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');
date_default_timezone_set('Asia/Damascus');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

(function () {
    $envPaths = [
        __DIR__ . '/../.env',
        __DIR__ . '/.env',
        dirname(__DIR__, 2) . '/.env'
    ];
    foreach ($envPaths as $p) {
        if (is_readable($p)) {
            foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;
                [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                putenv(trim($k) . '=' . trim($v));
            }
            break;
        }
    }
})();

function env($key, $default = null) {
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
}

const DB_HOST = 'sql209.hstn.me';
const DB_PORT = 3306;
const DB_NAME = 'mseet_43070153_Omni';
const DB_USER = 'mseet_43070153';
const DB_PASS = 'Mam27370';
const APP_NAME = 'Omni';
const APP_URL = 'https://omni.zya.me';
const ADMIN_EMAIL = 'omniamarkeet@gmail.com';

$jwt_secret = env('OMNI_JWT_SECRET');
if (!$jwt_secret || strlen($jwt_secret) < 32) {
    error_log('FATAL: OMNI_JWT_SECRET missing or too short');
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
}

define('JWT_SECRET', $jwt_secret);
define('JWT_EXPIRE', 60 * 60 * 24 * 30);

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function error_response($msg, $code = 400) {
    respond(['error' => $msg], $code);
}

function get_json_input() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    $cache = is_array($d) ? $d : [];
    return $cache;
}

function get_param($key, $default = null) {
    $data = get_json_input();
    if (isset($data[$key])) return $data[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key])) return $_GET[$key];
    return $default;
}

function clean_input($s) {
    return trim(strip_tags((string)$s));
}

function make_uuid() {
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    $h = bin2hex($b);
    return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20,12);
}

function b64e($d) { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function b64d($d) { return base64_decode(strtr($d, '-_', '+/')); }

function make_token($uid) {
    $h = b64e(json_encode(['typ'=>'JWT','alg'=>'HS256']));
    $p = b64e(json_encode(['sub'=>$uid,'iat'=>time(),'exp'=>time()+JWT_EXPIRE]));
    $s = b64e(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    return "$h.$p.$s";
}

function verify_token($t) {
    $parts = explode('.', $t);
    if (count($parts) !== 3) return null;
    $calc = b64e(hash_hmac('sha256', "$parts[0].$parts[1]", JWT_SECRET, true));
    if (!hash_equals($calc, $parts[2])) return null;
    $payload = json_decode(b64d($parts[1]), true);
    if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) return null;
    return $payload;
}

function normalize_phone_num($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    $phone = str_replace('+', '', $phone);
    if (substr($phone,0,2) === '00') $phone = substr($phone,2);
    if (substr($phone,0,1) === '0') $phone = '963'.substr($phone,1);
    if (substr($phone,0,3) !== '963' && (strlen($phone)===9 || strlen($phone)===10)) {
        $phone = '963'.$phone;
    }
    return $phone;
}

function valid_phone($p) {
    return preg_match('/^[1-9][0-9]{8,14}$/', normalize_phone_num($p));
}

function format_phone_display($p) {
    if (!$p) return '';
    $x = normalize_phone_num($p);
    if (substr($x,0,3) === '963') return '+963 '.substr($x,3,3).' '.substr($x,6,3).' '.substr($x,9);
    return '+'.$x;
}

function make_account_number($pdo) {
    for ($i = 0; $i < 20; $i++) {
        $num = str_pad((string)random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('SELECT id FROM profiles WHERE account_number = ?');
        $stmt->execute([$num]);
        if (!$stmt->fetch()) return $num;
    }
    throw new Exception('فشل توليد رقم حساب');
}

function get_bearer_user($pdo) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/i', $auth, $m)) return null;
    $payload = verify_token(trim($m[1]));
    if (!$payload) return null;
    $stmt = $pdo->prepare('SELECT * FROM profiles WHERE id = ?');
    $stmt->execute([$payload['sub']]);
    return $stmt->fetch() ?: null;
}

function require_auth($pdo) {
    $u = get_bearer_user($pdo);
    if (!$u) error_response('يجب تسجيل الدخول', 401);
    return $u;
}

function user_public($u) {
    return [
        'id' => $u['id'],
        'name' => $u['name'],
        'phone' => format_phone_display($u['phone']),
        'email' => $u['email'],
        'role' => $u['role'],
        'account_number' => $u['account_number'],
        'balance_omni' => (float)($u['balance_omni'] ?? 0),
        'balance_syp' => (float)($u['balance_syp'] ?? 0),
        'user_type' => $u['user_type'] ?? 'regular',
        'store_name' => $u['store_name'] ?? null,
        'merchant_status' => $u['merchant_status'] ?? null,
        'telegram_linked' => (bool)($u['telegram_linked'] ?? false)
    ];
}

function get_fee($pdo, $key, $default = 0) {
    $stmt = $pdo->prepare('SELECT value FROM fee_settings WHERE `key` = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (float)$row['value'] : $default;
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false
    ]);
} catch (PDOException $e) {
    error_log('[DB] ' . $e->getMessage());
    respond(['error' => 'Database connection failed'], 500);
}

$route = $_GET['route'] ?? '';
$parts = $route ? explode('/', trim($route, '/')) : [];
$endpoint = $parts[0] ?? '';
$action = $parts[1] ?? '';

if ($endpoint === '' || $endpoint === 'test') {
    $r = ['app' => APP_NAME, 'status' => 'running', 'time' => date('Y-m-d H:i:s')];
    try {
        $r['db_connected'] = true;
        $r['users_count'] = (int)$pdo->query('SELECT COUNT(*) FROM profiles')->fetchColumn();
        $r['products_count'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    } catch (Exception $e) {
        $r['db_error'] = 'query failed';
    }
    respond($r);
}

if ($endpoint === 'auth') {
    if ($action === 'register') {
        $name = clean_input(get_param('name'));
        $phone = clean_input(get_param('phone'));
        $email = strtolower(clean_input(get_param('email', '')));
        $password = (string)get_param('password');
        $role = (string)get_param('role', 'customer');
        $storeName = clean_input(get_param('storeName', ''));

        if (!in_array($role, ['customer', 'merchant'], true)) error_response('نوع حساب غير مسموح');
        if (!$name || !$phone || !$password) error_response('أكمل الحقول');
        if (!valid_phone($phone)) error_response('رقم هاتف غير صحيح');
        if (strlen($password) < 8) error_response('كلمة المرور 8 أحرف على الأقل');
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('بريد غير صحيح');

        $np = normalize_phone_num($phone);
        $chk = $pdo->prepare('SELECT id FROM profiles WHERE phone = ?');
        $chk->execute([$np]);
        if ($chk->fetch()) error_response('رقم مسجّل مسبقاً');
        if ($email) {
            $chk = $pdo->prepare('SELECT id FROM profiles WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) error_response('البريد مسجّل مسبقاً');
        }

        $id = make_uuid();
        $acc = make_account_number($pdo);
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        $stmt = $pdo->prepare('INSERT INTO profiles (id, name, phone, phone_country, email, password_hash, role, account_number, store_name, business_name, merchant_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id, $name, $np, '963', $email ?: null, $hash, $role, $acc,
            $role === 'merchant' ? $storeName : null,
            $role === 'merchant' ? $storeName : null,
            $role === 'merchant' ? 'pending' : 'approved'
        ]);

        $token = make_token($id);
        $user = $pdo->prepare('SELECT * FROM profiles WHERE id = ?');
        $user->execute([$id]);
        $u = $user->fetch();

        respond(['success' => true, 'message' => 'تم التسجيل', 'token' => $token, 'user' => user_public($u)]);
    }

    if ($action === 'login') {
        $identifier = clean_input(get_param('identifier'));
        $password = (string)get_param('password');
        if (!$identifier || !$password) error_response('أدخل البيانات');

        if (strpos($identifier, '@') !== false) {
            $stmt = $pdo->prepare('SELECT * FROM profiles WHERE email = ? LIMIT 1');
            $stmt->execute([strtolower($identifier)]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM profiles WHERE phone = ? LIMIT 1');
            $stmt->execute([normalize_phone_num($identifier)]);
        }
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            error_response('بيانات خاطئة', 401);
        }

        $pdo->prepare('UPDATE profiles SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?')->execute([$user['id']]);
        respond([
            'success' => true,
            'message' => 'مرحباً ' . $user['name'],
            'token' => make_token($user['id']),
            'user' => user_public($user)
        ]);
    }

    if ($action === 'me') {
        $u = require_auth($pdo);
        respond(['success' => true, 'user' => user_public($u)]);
    }
}

if ($endpoint === 'profile') {
    if ($action === 'get' || $action === '') {
        $u = require_auth($pdo);
        respond(['success' => true, 'user' => user_public($u)]);
    }

    if ($action === 'update') {
        $u = require_auth($pdo);
        $name = clean_input(get_param('name', $u['name']));
        $store = clean_input(get_param('store_name', $u['store_name'] ?? ''));
        $pdo->prepare('UPDATE profiles SET name = ?, store_name = ?, business_name = ? WHERE id = ?')
            ->execute([$name, $store, $store, $u['id']]);
        $stmt = $pdo->prepare('SELECT * FROM profiles WHERE id = ?');
        $stmt->execute([$u['id']]);
        respond(['success' => true, 'user' => user_public($stmt->fetch())]);
    }
}

if ($endpoint === 'wallet') {
    if ($action === 'balance' || $action === '') {
        $u = require_auth($pdo);
        respond([
            'success' => true,
            'balance_omni' => (float)$u['balance_omni'],
            'balance_syp' => (float)$u['balance_syp']
        ]);
    }

    if ($action === 'transactions') {
        $u = require_auth($pdo);
        $limit = max(1, min((int)get_param('limit', 50), 200));
        $stmt = $pdo->prepare('SELECT id, type, title, amount, currency, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . $limit);
        $stmt->execute([$u['id']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['amount'] = (float)$r['amount']; }
        respond(['success' => true, 'transactions' => $rows]);
    }

    if ($action === 'transfer') {
        $u = require_auth($pdo);
        $to = clean_input(get_param('to'));
        $amount = (float)get_param('amount');
        $note = clean_input(get_param('note', ''));

        if (!$to) error_response('أدخل المستلم');
        if ($amount <= 0) error_response('مبلغ غير صحيح');

        $stmt = $pdo->prepare('SELECT * FROM profiles WHERE phone = ? OR account_number = ? OR email = ? LIMIT 1');
        $stmt->execute([normalize_phone_num($to), $to, strtolower($to)]);
        $rec = $stmt->fetch();
        if (!$rec) error_response('المستلم غير موجود');
        if ($rec['id'] === $u['id']) error_response('لا يمكن التحويل لنفسك');

        $feePct = get_fee($pdo, 'transfer_fee_percent', 0.5);
        $fee = round($amount * $feePct / 100, 2);
        $total = $amount + $fee;

        $stmt = $pdo->prepare('SELECT balance_omni FROM profiles WHERE id = ? FOR UPDATE');
        $stmt->execute([$u['id']]);
        $senderBal = (float)$stmt->fetchColumn();
        if ($senderBal < $total) error_response('رصيد غير كافٍ');

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE profiles SET balance_omni = balance_omni - ? WHERE id = ?')->execute([$total, $u['id']]);
            $pdo->prepare('UPDATE profiles SET balance_omni = balance_omni + ? WHERE id = ?')->execute([$amount, $rec['id']]);
            $pdo->prepare("INSERT INTO transactions (id, user_id, type, title, amount, currency) VALUES (?, ?, 'out', ?, ?, 'OMNI')")->execute([make_uuid(), $u['id'], 'تحويل إلى ' . $rec['name'] . ($note ? ' — ' . $note : ''), $total]);
            $pdo->prepare("INSERT INTO transactions (id, user_id, type, title, amount, currency) VALUES (?, ?, 'in', ?, ?, 'OMNI')")->execute([make_uuid(), $rec['id'], 'استلام من ' . $u['name'], $amount]);
            $pdo->commit();
            respond(['success' => true, 'message' => 'تم التحويل', 'fee' => $fee]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[wallet/transfer] ' . $e->getMessage());
            error_response('فشلت العملية', 500);
        }
    }
}

if ($endpoint === 'products') {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT p.*, s.name AS store_name FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE p.active = 1 ORDER BY p.featured DESC, p.created_at DESC LIMIT 50");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['price'] = (float)$r['price'];
            $r['old_price'] = $r['old_price'] !== null ? (float)$r['old_price'] : null;
        }
        respond(['success' => true, 'products' => $rows]);
    }

    if ($action === 'get') {
        $id = clean_input(get_param('id'));
        if (!$id) error_response('id مطلوب');
        $stmt = $pdo->prepare('SELECT p.*, s.name AS store_name FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE p.id = ? AND p.active = 1 LIMIT 1');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) error_response('منتج غير موجود', 404);
        $p['price'] = (float)$p['price'];
        $p['old_price'] = $p['old_price'] !== null ? (float)$p['old_price'] : null;
        respond(['success' => true, 'product' => $p]);
    }
}

if ($endpoint === 'stores') {
    if ($action === 'list' || $action === '') {
        $stmt = $pdo->query('SELECT id, name, category, icon, phone FROM stores WHERE active = 1 ORDER BY name');
        respond(['success' => true, 'stores' => $stmt->fetchAll()]);
    }
}

if ($endpoint === 'orders') {
    if ($action === 'create') {
        $u = require_auth($pdo);
        $items = get_param('items', []);
        if (!is_array($items) || empty($items)) error_response('السلة فارغة');

        $customer_name = clean_input(get_param('customer_name', $u['name']));
        $customer_phone = clean_input(get_param('customer_phone', $u['phone']));
        $address = clean_input(get_param('address', ''));
        $city = clean_input(get_param('city', ''));
        $notes = clean_input(get_param('notes', ''));

        $subtotal = 0.0;
        foreach ($items as $it) {
            $pid = $it['id'] ?? null;
            $qty = (int)($it['quantity'] ?? 1);
            if (!$pid || $qty < 1) continue;
            $product = $pdo->prepare('SELECT * FROM products WHERE id = ? AND active = 1 LIMIT 1');
            $product->execute([$pid]);
            $row = $product->fetch();
            if (!$row) continue;
            $subtotal += (float)$row['price'] * $qty;
        }

        $delivery = (float)get_param('delivery_fee', 0);
        $total = $subtotal + $delivery;
        $orderId = make_uuid();
        $orderNumber = 'OM-' . date('ymdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $pdo->prepare('INSERT INTO orders (id, order_number, user_id, subtotal, delivery_fee, total, payment_method, customer_name, customer_phone, address, city, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$orderId, $orderNumber, $u['id'], $subtotal, $delivery, $total, 'OMNI', $customer_name, $customer_phone, $address, $city, $notes]);

        foreach ($items as $it) {
            $pid = $it['id'] ?? null;
            $qty = (int)($it['quantity'] ?? 1);
            if (!$pid || $qty < 1) continue;
            $product = $pdo->prepare('SELECT * FROM products WHERE id = ? AND active = 1 LIMIT 1');
            $product->execute([$pid]);
            $row = $product->fetch();
            if (!$row) continue;
            $pdo->prepare('INSERT INTO order_items (id, order_id, product_id, product_name, product_icon, price, quantity, store_id, store_name, merchant_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
                make_uuid(), $orderId, $row['id'], $row['name'], $row['icon'], $row['price'], $qty, $row['store_id'], (string)($pdo->query('SELECT name FROM stores WHERE id = "' . addslashes($row['store_id']) . '"')->fetchColumn() ?: ''), 'pending'
            ]);
        }

        respond(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber, 'total' => $total]);
    }

    if ($action === 'my') {
        $u = require_auth($pdo);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$u['id']]);
        respond(['success' => true, 'orders' => $stmt->fetchAll()]);
    }
}

if ($endpoint === 'admin') {
    if ($action === 'stats') {
        $u = require_auth($pdo);
        if ($u['role'] !== 'admin') error_response('غير مصرح', 403);
        $stats = [
            'users' => (int)$pdo->query('SELECT COUNT(*) FROM profiles')->fetchColumn(),
            'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'fund' => (float)$pdo->query('SELECT balance FROM omni_fund WHERE id = 1')->fetchColumn()
        ];
        respond(['success' => true, 'stats' => $stats]);
    }
}

respond(['error' => 'Endpoint not found'], 404);

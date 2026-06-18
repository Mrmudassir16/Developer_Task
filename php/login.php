<?php
header('Content-Type: application/json');

require_once 'db.php';

// Only allow POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter email and password.']);
    exit;
}

try {
    $pdo = getMySQLConnection();

    // Query user by email using MySQL Prepared Statements
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    // Generate secure random token
    $token = bin2hex(random_bytes(32));

    // Store session details in Redis
    $redis = getRedisConnection();
    $sessionData = json_encode([
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name']
    ]);

    // Save token to Redis with a 1-hour expiration
    $redisKey = "session:" . $token;
    $redis->set($redisKey, $sessionData, 3600);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'message' => 'Login successful!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

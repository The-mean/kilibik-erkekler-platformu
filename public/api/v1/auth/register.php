<?php
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$username = $data['username'];
$password = $data['password'];
$email = $data['email'];

// Kullanıcı adı ve email kontrolü
$existing = $db->fetchOne(
    "SELECT id FROM users WHERE username = :username OR email = :email",
    [
        ':username' => $username,
        ':email' => $email
    ]
);

if ($existing) {
    http_response_code(409);
    echo json_encode(['error' => 'Username or email already exists']);
    exit();
}

// Şifreyi hashle
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $db->beginTransaction();

    // Yeni kullanıcı ekle
    $userId = $db->insert('users', [
        'username' => $username,
        'password' => $hashedPassword,
        'email' => $email,
        'created_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ]);

    $db->commit();

    // JWT token oluştur
    $token = $jwtAuth->generateToken($userId);

    echo json_encode([
        'message' => 'Registration successful',
        'data' => [
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email
            ],
            'token' => $token
        ]
    ]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
} 
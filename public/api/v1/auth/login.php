<?php
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing credentials']);
    exit();
}

$username = $data['username'];
$password = $data['password'];

$user = $db->fetchOne(
    "SELECT id, username, password FROM users WHERE username = :username",
    [':username' => $username]
);

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit();
}

// JWT token oluştur
$token = $jwtAuth->generateToken($user['id']);

echo json_encode([
    'message' => 'Login successful',
    'data' => [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username']
        ],
        'token' => $token
    ]
]); 
<?php
header('Content-Type: application/json');

require_once 'db.php';

// Helper function to extract Bearer Token from headers
function getBearerToken() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } else if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    // Check for Bearer token format
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

$token = getBearerToken();

if (!$token) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Missing session token.']);
    exit;
}

try {
    // Connect to Redis to verify session
    $redis = getRedisConnection();
    $sessionKey = "session:" . $token;
    $sessionJson = $redis->get($sessionKey);

    if (!$sessionJson) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or expired session.']);
        exit;
    }

    // Parse user context from Redis session
    $userContext = json_decode($sessionJson, true);
    $email = $userContext['email'];
    $name = $userContext['name'];

    $mongo = getMongoConnection();
    $namespace = MONGO_DB . '.' . MONGO_COLLECTION;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Fetch Profile from MongoDB
        $filter = ['email' => $email];
        $options = ['limit' => 1];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $mongo->executeQuery($namespace, $query);
        $profiles = iterator_to_array($cursor);
        
        $profileData = null;
        if (!empty($profiles)) {
            $profileDoc = $profiles[0];
            $profileData = [
                'age' => isset($profileDoc->age) ? $profileDoc->age : '',
                'dob' => isset($profileDoc->dob) ? $profileDoc->dob : '',
                'contact' => isset($profileDoc->contact) ? $profileDoc->contact : '',
                'bio' => isset($profileDoc->bio) ? $profileDoc->bio : ''
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'name' => $name,
                'email' => $email,
                'profile' => $profileData
            ]
        ]);
        exit;

    } else if ($method === 'POST') {
        // Update Profile in MongoDB (Upsert)
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $dob = isset($_POST['dob']) ? trim($_POST['dob']) : null;
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : null;
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';

        if (empty($age) || empty($dob) || empty($contact)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Age, Date of Birth, and Contact are required fields.']);
            exit;
        }

        // Prepare bulk write upsert statement for MongoDB
        $bulk = new MongoDB\Driver\BulkWrite();
        $filter = ['email' => $email];
        $updateData = [
            '$set' => [
                'email' => $email,
                'age' => $age,
                'dob' => $dob,
                'contact' => $contact,
                'bio' => $bio,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $bulk->update($filter, $updateData, ['upsert' => true]);
        $result = $mongo->executeBulkWrite($namespace, $bulk);

        if ($result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0 || $result->getMatchedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            // Document matched but no changes were actually written
            echo json_encode(['success' => true, 'message' => 'No changes made. Profile is up to date.']);
        }
        exit;

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

<?php
// Database Configuration and Connection Helper

// MySQL Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '1234');
define('DB_NAME', getenv('DB_NAME') ?: 'internship');

// Redis Credentials
$redisUrl = getenv('REDIS_URL');
$redisHost = '127.0.0.1';
$redisPort = 6379;
$redisAuth = null;
if ($redisUrl) {
    $parsed = parse_url($redisUrl);
    if ($parsed) {
        $redisHost = $parsed['host'] ?? $redisHost;
        $redisPort = $parsed['port'] ?? $redisPort;
        if (!empty($parsed['pass'])) {
            $redisAuth = $parsed['pass'];
        } elseif (!empty($parsed['user'])) {
            $redisAuth = $parsed['user'];
        }
    }
} else {
    $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
    $redisPort = getenv('REDIS_PORT') !== false ? (int)getenv('REDIS_PORT') : 6379;
    $redisAuth = getenv('REDIS_AUTH') ?: null;
}
define('REDIS_HOST', $redisHost);
define('REDIS_PORT', $redisPort);
define('REDIS_AUTH', $redisAuth);

// MongoDB Credentials
define('MONGO_URI', getenv('MONGO_URI') ?: 'mongodb://127.0.0.1:27017');
define('MONGO_DB', getenv('MONGO_DB') ?: 'internship');
define('MONGO_COLLECTION', getenv('MONGO_COLLECTION') ?: 'profiles');

/**
 * Returns a MySQL PDO instance.
 */
function getMySQLConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            // First connect without DB to ensure we can create it if not exists
            $tempPdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            
            // Now connect to the database
            $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Create users table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");
            
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'MySQL connection error: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

/**
 * Custom Pure-PHP Redis Client using raw sockets.
 * This ensures the script works even if the native 'phpredis' extension is not installed.
 */
class RedisSocketClient {
    private $socket;

    public function __construct($host = '127.0.0.1', $port = 6379, $auth = null) {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$this->socket) {
            throw new Exception("Could not connect to Redis: " . $errstr);
        }
        if ($auth) {
            if (strpos($auth, ':') !== false) {
                list($user, $pass) = explode(':', $auth, 2);
                $this->execute(['AUTH', $user, $pass]);
            } else {
                $this->execute(['AUTH', $auth]);
            }
        }
    }

    public function set($key, $value, $ex = null) {
        if ($ex) {
            return $this->execute(['SET', $key, $value, 'EX', (string)$ex]);
        }
        return $this->execute(['SET', $key, $value]);
    }

    public function get($key) {
        return $this->execute(['GET', $key]);
    }

    public function del($key) {
        return $this->execute(['DEL', $key]);
    }

    private function execute(array $args) {
        $cmd = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $cmd .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }
        fwrite($this->socket, $cmd);
        return $this->readResponse();
    }

    private function readResponse() {
        $line = fgets($this->socket);
        if ($line === false) return null;
        $type = $line[0];
        $value = substr($line, 1, -2);

        switch ($type) {
            case '+': return $value; // Simple string
            case '-': throw new Exception("Redis error: " . $value);
            case ':': return (int)$value; // Integer
            case '$': // Bulk string
                $length = (int)$value;
                if ($length === -1) return null;
                $data = '';
                while (strlen($data) < $length) {
                    $chunk = fread($this->socket, $length - strlen($data));
                    if ($chunk === false) break;
                    $data .= $chunk;
                }
                fread($this->socket, 2); // Skip CRLF
                return $data;
            case '*': // Array
                $count = (int)$value;
                if ($count === -1) return null;
                $results = [];
                for ($i = 0; $i < $count; $i++) {
                    $results[] = $this->readResponse();
                }
                return $results;
        }
        return null;
    }
}

/**
 * Returns a Redis instance (either native extension class or custom socket fallback).
 */
function getRedisConnection() {
    static $redis = null;
    if ($redis === null) {
        try {
            if (class_exists('Redis')) {
                $redis = new Redis();
                $redis->connect(REDIS_HOST, REDIS_PORT);
                if (defined('REDIS_AUTH') && REDIS_AUTH !== null) {
                    if (strpos(REDIS_AUTH, ':') !== false) {
                        list($user, $pass) = explode(':', REDIS_AUTH, 2);
                        $redis->auth([$user, $pass]);
                    } else {
                        $redis->auth(REDIS_AUTH);
                    }
                }
            } else {
                $redis = new RedisSocketClient(REDIS_HOST, REDIS_PORT, defined('REDIS_AUTH') ? REDIS_AUTH : null);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Redis connection error: ' . $e->getMessage()]);
            exit;
        }
    }
    return $redis;
}

/**
 * Returns a MongoDB Driver Manager instance.
 */
function getMongoConnection() {
    static $manager = null;
    if ($manager === null) {
        try {
            if (!class_exists('MongoDB\Driver\Manager')) {
                throw new Exception("MongoDB extension is not installed or enabled in PHP configuration.");
            }
            $manager = new MongoDB\Driver\Manager(MONGO_URI);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'MongoDB connection error: ' . $e->getMessage()]);
            exit;
        }
    }
    return $manager;
}

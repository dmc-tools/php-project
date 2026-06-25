<?php
/**
 * Global PHP API Bridge for MySQL
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Load database configuration
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Configuration file (config.php) is missing. Please create it at ' . __DIR__ . '/config.php']);
    exit;
}

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // If local database fails to connect, proxy the request to the fallback remote API
    $remote_api = 'https://digitrainer.co.in/aicrm/Accounting-App-Surch/api.php';
    
    // Build parameters for forwarding
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => $_SERVER['REQUEST_METHOD'],
            'content' => file_get_contents('php://input'),
            'ignore_errors' => true,
            'timeout' => 5 // 5 second timeout
        ]
    ];
    
    // If it's a GET request (like action=test)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        $remote_url = $remote_api . $query;
    } else {
        $remote_url = $remote_api;
    }
    
    $context  = stream_context_create($options);
    $result = @file_get_contents($remote_url, false, $context);
    
    if ($result !== false) {
        echo $result;
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Local DB Connection Failed and Remote Fallback API was unreachable. Local Error: ' . $e->getMessage()
        ]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'test') {
    echo json_encode(['success' => true, 'message' => 'Connected to MySQL Instance Successfully.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Body - No JSON detected.']);
    exit;
}

$action = $input['action'] ?? '';
$table  = $input['table'] ?? '';
$data   = $input['data'] ?? [];

// --- Handle Generic CRUD Actions ---
if (empty($table)) {
    echo json_encode(['success' => false, 'message' => "Table name is required for action '{$action}'."]);
    exit;
}

try {
    switch ($action) {
        case 'select':
            $where = "";
            $params = [];
            if (!empty($data)) {
                $where = " WHERE ";
                $conds = [];
                foreach ($data as $k => $v) {
                    $conds[] = "`$k` = :$k";
                    $params[$k] = $v;
                }
                $where .= implode(" AND ", $conds);
            }
            $sql = "SELECT * FROM `$table` $where";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'insert':
            $columns = implode("`, `", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $sql = "INSERT INTO `$table` (`$columns`) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'update':
            $id = $data['id'];
            unset($data['id']);
            $fields = "";
            foreach ($data as $key => $value) {
                $fields .= "`$key` = :$key, ";
            }
            $fields = rtrim($fields, ", ");
            $sql = "UPDATE `$table` SET $fields WHERE `id` = :id";
            $data['id'] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $sql = "DELETE FROM `$table` WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $data['id']]);
            echo json_encode(['success' => true]);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => "Action '$action' Not Supported"]);
            break;
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}

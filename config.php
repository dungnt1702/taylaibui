<?php
// Configuration file to automatically detect environment
// and include appropriate database configuration

// Function to detect environment
function detectEnvironment() {
    // Method 1: Check server hostname/IP
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
    
    // Debug logging for environment detection
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("=== Environment Detection Debug ===");
        error_log("SERVER_NAME: " . $server_name);
        error_log("SERVER_ADDR: " . $server_addr);
        error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set'));
        error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set'));
    }
    
    // Method 2: Check if running on localhost
    $is_localhost = in_array($server_name, ['localhost', '127.0.0.1', '::1']) ||
                    in_array($server_addr, ['127.0.0.1', '::1']) ||
                    strpos($server_name, 'localhost') !== false ||
                    strpos($server_addr, '127.0.0.1') !== false ||
                    // Check if running from command line (CLI)
                    php_sapi_name() === 'cli' ||
                    // Check if no server variables are set (likely CLI)
                    empty($server_name) && empty($server_addr);
    
    // Method 3: Check if running on development machine
    $is_dev = $is_localhost || 
              strpos($server_name, '.local') !== false ||
              strpos($server_name, '.test') !== false ||
              strpos($server_name, '.dev') !== false ||
              $server_name === 'localhost:8000' ||
              $server_name === '127.0.0.1:8000';
    
    // Method 4: Check for specific production domains
    $production_domains = [
        'qlss.taylaibui.vn',       // Production domain
        'www.qlss.taylaibui.vn',   // Production domain with www
        'tay99672.qlss.com',       // Production domain
        'www.tay99672.qlss.com',   // Production domain with www
        'qlss.com',                 // Alternative domain
        'www.qlss.com'              // Alternative domain with www
    ];
    
    $is_production = in_array($server_name, $production_domains);
    
    // Method 4b: Check if not localhost (alternative production detection)
    $is_not_localhost = !$is_localhost && 
                        $server_name !== 'localhost' && 
                        $server_name !== '127.0.0.1' && 
                        $server_name !== '::1' &&
                        strpos($server_name, 'localhost') === false &&
                        strpos($server_name, '127.0.0.1') === false;
    
    // Method 5: Check environment variable (if set)
    $env_var = getenv('APP_ENV') ?: getenv('ENVIRONMENT');
    if ($env_var) {
        if (in_array(strtolower($env_var), ['production', 'prod', 'live'])) {
            return 'production';
        } elseif (in_array(strtolower($env_var), ['development', 'dev', 'local'])) {
            return 'development';
        }
    }
    
    // Method 6: Check for specific file existence
    if (file_exists(__DIR__ . '/.env.production')) {
        return 'production';
    } elseif (file_exists(__DIR__ . '/env.production')) {
        return 'production';
    } elseif (file_exists(__DIR__ . '/.env.local')) {
        return 'development';
    }
    
    // Method 7: Check for env.local file (alternative naming)
    if (file_exists(__DIR__ . '/env.local')) {
        return 'development';
    }
    
    // Default logic based on server detection
    if ($is_localhost) {
        return 'development';
    } elseif ($is_production) {
        return 'production';
    } elseif ($is_dev) {
        return 'development';
    } elseif ($is_not_localhost) {
        // If not localhost and not explicitly production, assume production
        return 'production';
    } else {
        // If unsure, default to development for safety
        return 'development';
    }
}

// Function to include appropriate database file
function includeDatabase() {
    $environment = detectEnvironment();
    
    // Log environment detection (for debugging)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Environment detected: " . $environment);
    }
    
    switch ($environment) {
        case 'production':
            if (file_exists(__DIR__ . '/db_production.php')) {
                require_once __DIR__ . '/db_production.php';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("Included: db_production.php");
                }
            } else {
                // Fallback to development database if production file doesn't exist
                require_once __DIR__ . '/db_development.php';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("Fallback: db_development.php (production file not found)");
                }
            }
            break;
            
        case 'development':
        default:
            if (file_exists(__DIR__ . '/db_development.php')) {
                require_once __DIR__ . '/db_development.php';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("Included: db_development.php");
                }
            } else {
                die("Database configuration file not found!");
            }
            break;
    }
}

// Set debug mode (set to true for debugging, false for production)
define('DEBUG_MODE', true);

// Auto-include database configuration
$db_result = includeDatabase();

// Verify database connection
if ($db_result === false || !isset($mysqli) || $mysqli->connect_error) {
    if (DEBUG_MODE) {
        error_log("Database connection failed: " . ($mysqli->connect_error ?? 'mysqli not set'));
    }
    // Không die() ngay lập tức, để các trang có thể xử lý lỗi riêng
    error_log("Database connection failed! Please check your configuration.");
}

// Optional: Set environment-specific constants
$environment = detectEnvironment();
define('CURRENT_ENVIRONMENT', $environment);
define('IS_PRODUCTION', $environment === 'production');
define('IS_DEVELOPMENT', $environment === 'development');

// Environment-specific settings
if (IS_PRODUCTION) {
    // Production settings
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
    
    // Production timezone
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    
} else {
    // Development settings
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 0);
    
    // Development timezone
    date_default_timezone_set('Asia/Ho_Chi_Minh');
}

// Log environment info (only in debug mode)
if (DEBUG_MODE) {
    error_log("=== Environment Info ===");
    error_log("Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown'));
    error_log("Environment: " . $environment);
    error_log("Database: " . (isset($mysqli) ? 'Connected' : 'Not connected'));
    error_log("=======================");
}
?>

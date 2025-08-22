<?php
// Configuration file to automatically detect environment
// and include appropriate database configuration

// Function to detect environment
function detectEnvironment() {
    // Method 1: Check server hostname/IP
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
    
    // Method 2: Check if running on localhost
    $is_localhost = in_array($server_name, ['localhost', '127.0.0.1', '::1']) ||
                    in_array($server_addr, ['127.0.0.1', '::1']);
    
    // Method 3: Check if running on development machine
    $is_dev = $is_localhost || 
              strpos($server_name, '.local') !== false ||
              strpos($server_name, '.test') !== false ||
              strpos($server_name, '.dev') !== false;
    
    // Method 4: Check for specific production domains
    $production_domains = [
        'taylaibui.com',           // Replace with your actual domain
        'www.taylaibui.com',       // Replace with your actual domain
        'yourdomain.com',          // Replace with your actual domain
        'www.yourdomain.com'       // Replace with your actual domain
    ];
    
    $is_production = in_array($server_name, $production_domains);
    
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
    } elseif (file_exists(__DIR__ . '/.env.local')) {
        return 'development';
    }
    
    // Default logic based on server detection
    if ($is_production) {
        return 'production';
    } elseif ($is_dev) {
        return 'development';
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
                // Fallback to local database if production file doesn't exist
                require_once __DIR__ . '/db.php';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("Fallback: db.php (production file not found)");
                }
            }
            break;
            
        case 'development':
        default:
            if (file_exists(__DIR__ . '/db.php')) {
                require_once __DIR__ . '/db.php';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("Included: db.php");
                }
            } else {
                die("Database configuration file not found!");
            }
            break;
    }
}

// Set debug mode (set to true for debugging, false for production)
define('DEBUG_MODE', false);

// Auto-include database configuration
includeDatabase();

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

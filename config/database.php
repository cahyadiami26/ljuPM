<?php
/**
 * Database Configuration
 * Koneksi ke database project management dan database user (existing)
 */

// Database project management (database baru)
define('DB_PM_HOST', 'localhost');
define('DB_PM_NAME', 'ljucoid_pm');
define('DB_PM_USER', '');
define('DB_PM_PASS', '');
define('DB_PM_CHARSET', 'utf8mb4');

// Database user existing (database berbeda di server yang sama)
define('DB_USER_HOST', 'localhost');
define('DB_USER_NAME', 'ljucoid_dms'); // Sesuaikan dengan nama database user existing
define('DB_USER_USER', '');
define('DB_USER_PASS', '');
define('DB_USER_CHARSET', 'utf8mb4');

/**
 * Class Database - Singleton Pattern
 */
class Database {
    private static $pmInstance = null;
    private static $userInstance = null;
    
    /**
     * Get Project Management Database Connection
     */
    public static function getPMConnection() {
        if (self::$pmInstance === null) {
            try {
                self::$pmInstance = new PDO(
                    "mysql:host=" . DB_PM_HOST . ";dbname=" . DB_PM_NAME . ";charset=" . DB_PM_CHARSET,
                    DB_PM_USER,
                    DB_PM_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch (PDOException $e) {
                error_log("PM Database Connection Error: " . $e->getMessage());
                die("Koneksi database project management gagal. Silakan hubungi administrator.");
            }
        }
        return self::$pmInstance;
    }
    
    /**
     * Get User Database Connection (existing)
     */
    public static function getUserConnection() {
        if (self::$userInstance === null) {
            try {
                self::$userInstance = new PDO(
                    "mysql:host=" . DB_USER_HOST . ";dbname=" . DB_USER_NAME . ";charset=" . DB_USER_CHARSET,
                    DB_USER_USER,
                    DB_USER_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch (PDOException $e) {
                error_log("User Database Connection Error: " . $e->getMessage());
                die("Koneksi database user gagal. Silakan hubungi administrator.");
            }
        }
        return self::$userInstance;
    }
}

/**
 * Helper function untuk quick access
 */
function db() {
    return Database::getPMConnection();
}

function dbUser() {
    return Database::getUserConnection();
}
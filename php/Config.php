<?php
/**
 * Configuration Class
 * Centralized configuration constants for the application
 */
class Config {
    
    // Security Configuration
    const SESSION_TIMEOUT = 1800; // 30 minutes in seconds
    const MAX_LOGIN_ATTEMPTS = 5;
    const ACCOUNT_LOCK_DURATION = 900; // 15 minutes in seconds
    
    // Password Requirements
    const PASSWORD_MIN_LENGTH = 8;
    const PASSWORD_REQUIRE_UPPERCASE = true;
    const PASSWORD_REQUIRE_LOWERCASE = true;
    const PASSWORD_REQUIRE_NUMBER = true;
    const PASSWORD_REQUIRE_SPECIAL = true;
    
    // File Upload Configuration
    const MAX_IMAGE_SIZE = 10485760; // 10MB in bytes
    const MAX_VIDEO_SIZE = 52428800; // 50MB in bytes
    const MAX_DOCUMENT_SIZE = 20971520; // 20MB in bytes
    const UPLOAD_PATH = 'php/images/';
    
    // Message Configuration
    const MAX_MESSAGE_LENGTH = 1000;
    const TYPING_TIMEOUT = 5; // seconds before typing indicator expires
    
    // Allowed Reactions
    const ALLOWED_REACTIONS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
    
    // Feature Flags
    const ENABLE_FILE_SHARING = false; // Not yet implemented
    const ENABLE_VIDEO_CALLS = false; // Not yet implemented
    const ENABLE_GROUPS = false; // Not yet implemented
    
    // API Configuration
    const API_VERSION = 'v1';
    const API_RATE_LIMIT = 100; // requests per minute (to be implemented)
    
    // Application Settings
    const APP_NAME = 'Realtime Chat App';
    const APP_ENVIRONMENT = 'development'; // development, staging, production
    const APP_DEBUG = true;
    
    /**
     * Get configuration value
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        if (defined("self::$key")) {
            return constant("self::$key");
        }
        return $default;
    }
    
    /**
     * Check if feature is enabled
     * @param string $feature Feature name
     * @return bool
     */
    public static function isEnabled($feature) {
        $key = 'ENABLE_' . strtoupper($feature);
        return self::get($key, false);
    }
}
?>

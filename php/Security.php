<?php
/**
 * Security Class
 * Handles security-related functions including CSRF tokens, XSS protection, and rate limiting
 */
class Security {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate CSRF Token
     */
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF Token
     */
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
    
    /**
     * XSS Protection - sanitize output
     */
    public function sanitizeOutput($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate password strength
     * Minimum 8 characters, at least one uppercase, one lowercase, one number, one special character
     */
    public function validatePasswordStrength($password) {
        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return "Password must contain at least one special character";
        }
        return true;
    }
    
    /**
     * Check rate limiting for login attempts
     */
    public function checkLoginRateLimit($email) {
        $stmt = $this->conn->prepare("SELECT failed_login_attempts, account_locked_until FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Check if account is locked
            if ($row['account_locked_until'] && strtotime($row['account_locked_until']) > time()) {
                $stmt->close();
                return "Account is temporarily locked. Please try again later.";
            }
            
            // Check failed attempts
            if ($row['failed_login_attempts'] >= 5) {
                // Lock account for 15 minutes
                $lockUntil = date('Y-m-d H:i:s', time() + 900);
                $updateStmt = $this->conn->prepare("UPDATE users SET account_locked_until = ? WHERE email = ?");
                $updateStmt->bind_param("ss", $lockUntil, $email);
                $updateStmt->execute();
                $updateStmt->close();
                $stmt->close();
                return "Too many failed attempts. Account locked for 15 minutes.";
            }
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Increment failed login attempts
     */
    public function incrementFailedLogin($email) {
        $stmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Reset failed login attempts on successful login
     */
    public function resetFailedLogin($email) {
        $stmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Set secure headers
     */
    public static function setSecureHeaders() {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://kit.fontawesome.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://ka-f.fontawesome.com;");
    }
    
    /**
     * Validate session and check for timeout
     */
    public function validateSession() {
        // Session timeout after 30 minutes of inactivity
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
}

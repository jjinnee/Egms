<?php

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Hash device-related data for security
     * Uses SHA-256 to hash sensitive device information
     * 
     * @param mixed $data - The data to hash (string, array, or object)
     * @param string|null $salt - Optional salt for additional security
     * @return string - The hashed data
     */
    public static function hashDeviceData($data, ?string $salt = null): string
    {
        // Convert arrays/objects to JSON string
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        
        // Add salt if provided, otherwise use app key
        $salt = $salt ?? config('app.key', 'default-salt');
        
        // Hash using SHA-256
        return hash('sha256', $data . $salt);
    }

    /**
     * Hash device ID specifically
     * 
     * @param string $deviceId - The device ID to hash
     * @return string - The hashed device ID
     */
    public static function hashDeviceId(string $deviceId): string
    {
        return self::hashDeviceData($deviceId);
    }

    /**
     * Hash multiple device fields
     * 
     * @param array $deviceData - Array of device data fields
     * @param array $fieldsToHash - Specific fields to hash (optional)
     * @return array - The data with specified fields hashed
     */
    public static function hashDeviceFields(array $deviceData, array $fieldsToHash = ['device_id', 'contact_number']): array
    {
        foreach ($fieldsToHash as $field) {
            if (isset($deviceData[$field]) && !empty($deviceData[$field])) {
                $deviceData[$field . '_hash'] = self::hashDeviceData($deviceData[$field]);
            }
        }
        
        return $deviceData;
    }

    /**
     * Verify if raw data matches a hash
     * 
     * @param mixed $rawData - The raw data to verify
     * @param string $hash - The hash to compare against
     * @param string|null $salt - Optional salt used during hashing
     * @return bool - True if matches, false otherwise
     */
    public static function verifyHash($rawData, string $hash, ?string $salt = null): bool
    {
        return hash_equals($hash, self::hashDeviceData($rawData, $salt));
    }

    /**
     * Generate a secure token for device authentication
     * 
     * @param string $deviceId - The device ID
     * @param int $expiresIn - Token expiration in seconds (default: 1 hour)
     * @return array - Array containing token and expiration timestamp
     */
    public static function generateDeviceToken(string $deviceId, int $expiresIn = 3600): array
    {
        $timestamp = time();
        $expiration = $timestamp + $expiresIn;
        $payload = $deviceId . '|' . $timestamp . '|' . $expiration;
        
        return [
            'token' => self::hashDeviceData($payload),
            'expires_at' => $expiration,
            'device_id_hash' => self::hashDeviceId($deviceId),
        ];
    }

    /**
     * Sanitize device data before storage
     * Removes or hashes sensitive information
     * 
     * @param array $data - The device data
     * @return array - Sanitized data
     */
    public static function sanitizeDeviceData(array $data): array
    {
        // Fields that should be hashed before logging/storage
        $sensitiveFields = ['ip_address', 'mac_address', 'contact_number'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = self::hashDeviceData($data[$field]);
            }
        }
        
        return $data;
    }
}


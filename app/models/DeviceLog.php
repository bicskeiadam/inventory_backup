<?php
require_once __DIR__ . '/../core/Model.php';
use Detection\MobileDetect;

class DeviceLog extends Model
{
    public function log(int $userId, string $userAgent)
    {
        $detect = new MobileDetect();
        $deviceType = $detect->isMobile() ? 'mobile' : ($detect->isTablet() ? 'tablet' : 'desktop');
        // Browser & OS: crude extraction
        $os = $detect->is('iOS') ? 'iOS' : ($detect->is('AndroidOS') ? 'Android' : '');
        $browser = parse_user_agent($userAgent)['browser'] ?? null; // helper below

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $country = $city = $isp = null;
        try {
            $json = file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city,isp");
            $info = json_decode($json, true);
            if (isset($info['status']) && $info['status'] == 'success') {
                $country = $info['country'] ?? null;
                $city = $info['city'] ?? null;
                $isp = $info['isp'] ?? null;
            }
        } catch (Exception $e) {
        }

        // Truncate to match DB column length (varchar(50)) to avoid 1265 Data truncated error
        $os = substr($os, 0, 50);
        $browser = substr($browser ?? '', 0, 50);

        $stmt = $this->pdo->prepare("INSERT INTO user_devices (user_id, device_type, os, browser, ip_address, country, city, isp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $deviceType, $os, $browser, $ip, $country, $city, $isp]);
    }
    public function getAll(int $limit = 100)
    {
        $stmt = $this->pdo->prepare("
            SELECT ud.*, u.first_name, u.last_name, u.email 
            FROM user_devices ud 
            JOIN users u ON ud.user_id = u.id 
            ORDER BY ud.created_at DESC 
            LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

/**
 * Egyszerű user agent parse helper (nem teljes)
 */
function parse_user_agent($ua)
{
    $browsers = ['Firefox', 'Chrome', 'Safari', 'Opera', 'Edge', 'MSIE'];
    foreach ($browsers as $b) {
        if (stripos($ua, $b) !== false)
            return ['browser' => $b];
    }
    return ['browser' => 'Unknown'];
}

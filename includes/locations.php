<?php
/**
 * Locations API
 * Returns provinces and cities data
 */

require_once 'config.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_provinces':
        getProvinces();
        break;
    case 'get_cities':
        $provinceId = isset($_GET['province_id']) ? intval($_GET['province_id']) : 0;
        getCities($provinceId);
        break;
    case 'get_all':
        getAllLocations();
        break;
    default:
        echo json_encode(['error' => 'Action not specified']);
}

/**
 * Get all provinces
 */
function getProvinces() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM provinces ORDER BY name");
        $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'provinces' => $provinces]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get cities by province ID
 */
function getCities($provinceId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM cities WHERE province_id = ? ORDER BY name");
        $stmt->execute([$provinceId]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'cities' => $cities]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get all locations (provinces and cities)
 */
function getAllLocations() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM provinces ORDER BY name");
        $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($provinces as &$province) {
            $stmt = $pdo->prepare("SELECT * FROM cities WHERE province_id = ? ORDER BY name");
            $stmt->execute([$province['id']]);
            $province['cities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'locations' => $provinces]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

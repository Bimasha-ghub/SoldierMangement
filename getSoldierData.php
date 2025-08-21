<?php
header('Content-Type: application/json');

include 'dbconnection.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the Army ID from POST data
    $armyId = $_POST['army_id'] ?? '';
    
    if (empty($armyId)) {
        echo json_encode(['error' => 'Army ID is required']);
        exit;
    }
    
    // Get soldier personal details from soldetails table
    $stmt = $conn->prepare("SELECT * FROM soldetails WHERE Aid = ?");
    $stmt->execute([$armyId]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$details) {
        echo json_encode(['error' => 'Soldier not found with Army ID: ' . $armyId]);
        exit;
    }
    
    // Get education details
    $stmt = $conn->prepare("SELECT * FROM soleducation WHERE Aid = ?");
    $stmt->execute([$armyId]);
    $education = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get position history from all position tables
    $positions = [];
    
    // Check admin table
    try {
        $stmt = $conn->prepare("SELECT 'Admin' as position, starteddate, enddate, serviceperiod FROM admin WHERE Aid = ?");
        $stmt->execute([$armyId]);
        $adminPositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $positions = array_merge($positions, $adminPositions);
    } catch (PDOException $e) {
        // Table might not exist or error occurred, continue
    }
    
    // Check battalionofficer table
    try {
        $stmt = $conn->prepare("SELECT 'Battalion Officer' as position, starteddate, enddate, serviceperiod FROM battalionofficer WHERE Aid = ?");
        $stmt->execute([$armyId]);
        $battalionPositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $positions = array_merge($positions, $battalionPositions);
    } catch (PDOException $e) {
        // Table might not exist or error occurred, continue
    }
    
    // Check colonelofregiment table
    try {
        $stmt = $conn->prepare("SELECT 'Colonel of Regiment' as position, starteddate, enddate, serviceperiod FROM colonelofregiment WHERE Aid = ?");
        $stmt->execute([$armyId]);
        $colonelPositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $positions = array_merge($positions, $colonelPositions);
    } catch (PDOException $e) {
        // Table might not exist or error occurred, continue
    }
    
    // Check normalsoldier table
    try {
        $stmt = $conn->prepare("SELECT 'Normal Soldier' as position, starteddate, enddate, serviceperiod FROM normalsoldier WHERE Aid = ?");
        $stmt->execute([$armyId]);
        $normalPositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $positions = array_merge($positions, $normalPositions);
    } catch (PDOException $e) {
        // Table might not exist or error occurred, continue
    }
    
    // Sort positions by start date (most recent first)
    usort($positions, function($a, $b) {
        if (empty($a['starteddate']) && empty($b['starteddate'])) return 0;
        if (empty($a['starteddate'])) return 1;
        if (empty($b['starteddate'])) return -1;
        return strtotime($b['starteddate']) - strtotime($a['starteddate']);
    });
    
    // Format dates in positions array
    foreach ($positions as &$position) {
        // Rename starteddate to startdate for consistency with frontend
        if (isset($position['starteddate'])) {
            $position['startdate'] = $position['starteddate'];
            unset($position['starteddate']);
        }
        
        if (!empty($position['startdate'])) {
            $position['startdate'] = date('Y-m-d', strtotime($position['startdate']));
        }
        if (!empty($position['enddate']) && $position['enddate'] !== '0000-00-00' && !is_null($position['enddate'])) {
            $position['enddate'] = date('Y-m-d', strtotime($position['enddate']));
        } else {
            $position['enddate'] = 'Current';
        }
    }
    
    // Format dates in details array
    if (!empty($details['dob']) && $details['dob'] !== '0000-00-00') {
        $details['dob'] = date('Y-m-d', strtotime($details['dob']));
    }
    if (!empty($details['joinDate']) && $details['joinDate'] !== '0000-00-00') {
        $details['joinDate'] = date('Y-m-d', strtotime($details['joinDate']));
    }
    if (!empty($details['assignDate']) && $details['assignDate'] !== '0000-00-00') {
        $details['assignDate'] = date('Y-m-d', strtotime($details['assignDate']));
    }
    
    // Return all data
    $response = [
        'success' => true,
        'details' => $details,
        'education' => $education,
        'positions' => $positions
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'System error: ' . $e->getMessage()
    ]);
}
?>
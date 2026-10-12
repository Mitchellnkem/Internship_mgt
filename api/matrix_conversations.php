<?php
require_once __DIR__.'/matrix_common.php';
matrix_require_login();
$currentUserId = (int)$_SESSION['SBUser'];
$currentUser = get_user_by_id($currentUserId);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!in_array($currentUser['user_type'], array('student', 'employer'), true)) {
        matrix_json_response(403, array('ok' => false, 'message' => 'Secure conversations are available to students and employers.'));
    }
    if ($currentUser['user_type'] === 'student') {
        $statement = $db->prepare("SELECT a.id AS application_id, i.title, i.company, i.location, i.employer_id AS peer_user_id, employer.full_name AS peer_name, peer_link.matrix_user_id AS peer_matrix_user_id, own_link.matrix_user_id AS own_matrix_user_id, c.matrix_room_id FROM applications a INNER JOIN internships i ON i.id = a.int_id INNER JOIN users employer ON employer.id = i.employer_id AND employer.user_type = 'employer' LEFT JOIN matrix_user_links peer_link ON peer_link.user_id = employer.id LEFT JOIN matrix_user_links own_link ON own_link.user_id = a.cus_id LEFT JOIN matrix_conversations c ON c.application_id = a.id WHERE a.cus_id = ? ORDER BY a.application_date DESC LIMIT 100");
    } else {
        $statement = $db->prepare("SELECT a.id AS application_id, i.title, i.company, i.location, a.cus_id AS peer_user_id, student.full_name AS peer_name, peer_link.matrix_user_id AS peer_matrix_user_id, own_link.matrix_user_id AS own_matrix_user_id, c.matrix_room_id FROM applications a INNER JOIN internships i ON i.id = a.int_id INNER JOIN users student ON student.id = a.cus_id AND student.user_type = 'student' LEFT JOIN matrix_user_links peer_link ON peer_link.user_id = student.id LEFT JOIN matrix_user_links own_link ON own_link.user_id = i.employer_id LEFT JOIN matrix_conversations c ON c.application_id = a.id WHERE i.employer_id = ? ORDER BY a.application_date DESC LIMIT 100");
    }
    $statement->bind_param('i', $currentUserId);
    $statement->execute();
    $result = $statement->get_result();
    $conversations = array();
    while ($row = $result->fetch_assoc()) {
        $conversations[] = array(
            'application_id' => (int)$row['application_id'],
            'title' => $row['title'],
            'company' => $row['company'],
            'location' => $row['location'],
            'peer_name' => $row['peer_name'],
            'peer_matrix_user_id' => $row['peer_matrix_user_id'],
            'account_linked' => !empty($row['own_matrix_user_id']) && !empty($row['peer_matrix_user_id']),
            'matrix_room_id' => $row['matrix_room_id']
        );
    }
    $statement->close();
    matrix_json_response(200, array('ok' => true, 'configured' => MATRIX_HOMESERVER_URL !== '' && MATRIX_SERVER_NAME !== '', 'matrix_user_id' => matrix_current_link($db, $currentUserId), 'conversations' => $conversations));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    matrix_json_response(405, array('ok' => false, 'message' => 'Method not allowed.'));
}
matrix_require_csrf();
$body = matrix_read_json_body();
$applicationId = isset($body['application_id']) ? (int)$body['application_id'] : 0;
$roomId = isset($body['room_id']) && is_string($body['room_id']) ? $body['room_id'] : '';
$accessToken = matrix_access_token_from_body($body);
if ($applicationId < 1 || strlen($roomId) > 255 || !preg_match('/^![A-Za-z0-9._=-]+:[A-Za-z0-9.:-]+$/', $roomId) || substr($roomId, -strlen(':'.MATRIX_SERVER_NAME)) !== ':'.MATRIX_SERVER_NAME) {
    matrix_json_response(422, array('ok' => false, 'message' => 'The application or Matrix room is invalid.'));
}
$currentMatrixId = matrix_verify_request_user($db, $currentUserId, $accessToken);

$lookup = $db->prepare("SELECT a.id, a.cus_id, i.employer_id, student.user_type AS student_type, employer.user_type AS employer_type, student_link.matrix_user_id AS student_matrix_id, employer_link.matrix_user_id AS employer_matrix_id FROM applications a INNER JOIN internships i ON i.id = a.int_id INNER JOIN users student ON student.id = a.cus_id INNER JOIN users employer ON employer.id = i.employer_id LEFT JOIN matrix_user_links student_link ON student_link.user_id = student.id LEFT JOIN matrix_user_links employer_link ON employer_link.user_id = employer.id WHERE a.id = ? LIMIT 1");
$lookup->bind_param('i', $applicationId);
$lookup->execute();
$application = $lookup->get_result()->fetch_assoc();
$lookup->close();
if (!$application || (int)$application['cus_id'] === (int)$application['employer_id'] || $application['student_type'] !== 'student' || $application['employer_type'] !== 'employer' || ($currentUserId !== (int)$application['cus_id'] && $currentUserId !== (int)$application['employer_id'])) {
    matrix_json_response(404, array('ok' => false, 'message' => 'No eligible applicant/employer conversation exists for this account.'));
}
if (empty($application['student_matrix_id']) || empty($application['employer_matrix_id'])) {
    matrix_json_response(409, array('ok' => false, 'message' => 'Both participants need to link their Matrix accounts first.'));
}
if (!hash_equals($currentMatrixId, $application['student_matrix_id']) && !hash_equals($currentMatrixId, $application['employer_matrix_id'])) {
    matrix_json_response(403, array('ok' => false, 'message' => 'Your linked Matrix identity is not a participant in this application.'));
}

// Persist a room only after the homeserver confirms encryption and exactly the
// two expected participants are joined. The access token is never stored/logged.
$encodedRoomId = rawurlencode($roomId);
$members = matrix_api_request('GET', '/_matrix/client/v3/rooms/'.$encodedRoomId.'/joined_members', $accessToken);
if ($members['status'] !== 200 || !isset($members['data']['joined']) || !is_array($members['data']['joined'])) {
    matrix_json_response(403, array('ok' => false, 'message' => 'Could not verify membership in that Matrix room.'));
}
$joinedIds = array_keys($members['data']['joined']);
$invited = matrix_api_request('GET', '/_matrix/client/v3/rooms/'.$encodedRoomId.'/members?membership=invite', $accessToken);
if ($invited['status'] !== 200 || !isset($invited['data']['chunk']) || !is_array($invited['data']['chunk'])) {
    matrix_json_response(403, array('ok' => false, 'message' => 'Could not verify the invitation in that Matrix room.'));
}
$invitedIds = array();
foreach ($invited['data']['chunk'] as $memberEvent) {
    if (isset($memberEvent['state_key'], $memberEvent['content']['membership']) && $memberEvent['content']['membership'] === 'invite') {
        $invitedIds[] = $memberEvent['state_key'];
    }
}
$expectedIds = array($application['student_matrix_id'], $application['employer_matrix_id']);
$memberIds = array_values(array_unique(array_merge($joinedIds, $invitedIds)));
sort($memberIds);
sort($expectedIds);
if (!in_array($currentMatrixId, $joinedIds, true) || $memberIds !== $expectedIds) matrix_json_response(403, array('ok' => false, 'message' => 'The private room must contain only the applicant and this internship’s employer.'));
$encryption = matrix_api_request('GET', '/_matrix/client/v3/rooms/'.$encodedRoomId.'/state/m.room.encryption/', $accessToken);
if ($encryption['status'] !== 200 || empty($encryption['data']['algorithm']) || $encryption['data']['algorithm'] !== 'm.megolm.v1.aes-sha2') {
    matrix_json_response(422, array('ok' => false, 'message' => 'This room is not using Matrix end-to-end encryption.'));
}

$existing = $db->prepare('SELECT matrix_room_id FROM matrix_conversations WHERE application_id = ? LIMIT 1');
$existing->bind_param('i', $applicationId);
$existing->execute();
$existingRoom = $existing->get_result()->fetch_assoc();
$existing->close();
if ($existingRoom) {
    if (hash_equals($existingRoom['matrix_room_id'], $roomId)) matrix_json_response(200, array('ok' => true, 'matrix_room_id' => $roomId));
    matrix_json_response(409, array('ok' => false, 'message' => 'A secure room is already linked to this application.'));
}
try {
    $insert = $db->prepare('INSERT INTO matrix_conversations (application_id, matrix_room_id, created_by_user_id) VALUES (?, ?, ?)');
    $insert->bind_param('isi', $applicationId, $roomId, $currentUserId);
    $insert->execute();
    $insert->close();
} catch (mysqli_sql_exception $exception) {
    error_log('BIT-InternshipHub Matrix room link failed: '.$exception->getMessage());
    matrix_json_response(409, array('ok' => false, 'message' => 'This Matrix room is already linked or the conversation was created in another session.'));
}
matrix_json_response(201, array('ok' => true, 'matrix_room_id' => $roomId));

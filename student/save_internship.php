<?php
require_once dirname(__DIR__).'/core/init.php';

if (!is_logged_in()) {
    flash('error_flash', 'Sign in with a student account to save internships.');
    redirect('../login.php');
}
if (empty($user_data['user_type']) || $user_data['user_type'] !== 'student' || !isset($user_data['account_status']) || $user_data['account_status'] !== 'active') {
    http_response_code(403);
    exit('Student accounts only.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$internshipId = isset($_POST['internship_id']) ? (int)$_POST['internship_id'] : 0;
$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
$returnTo = isset($_POST['return_to']) && is_string($_POST['return_to']) ? $_POST['return_to'] : '';
$redirectTarget = ($returnTo === 'details' && $internshipId > 0)
    ? '../application.php?id='.$internshipId
    : ($returnTo === 'account' ? '../myaccount.php#saved-opportunities' : '../myaccount.php#saved-opportunities');

if (!csrf_is_valid()) {
    flash('error_flash', 'Your session expired. Refresh the page and try again.');
    redirect($redirectTarget);
}
if ($internshipId < 1 || !in_array($action, array('save', 'remove'), true)) {
    flash('error_flash', 'Choose a valid internship and action.');
    redirect($redirectTarget);
}

$userId = (int)$_SESSION['SBUser'];
$transactionOpen = false;
try {
    if ($action === 'save') {
        $available = $db->prepare("SELECT id FROM internships WHERE id = ? AND status = 'published' LIMIT 1");
        $available->bind_param('i', $internshipId);
        $available->execute();
        $exists = $available->get_result()->num_rows > 0;
        $available->close();
        if (!$exists) {
            flash('error_flash', 'This internship is no longer available to save.');
            redirect($redirectTarget);
        }

        $db->begin_transaction();
        $transactionOpen = true;
        $lock = $db->prepare('SELECT id FROM users WHERE id = ? AND account_status = \'active\' FOR UPDATE');
        $lock->bind_param('i', $userId);
        $lock->execute();
        $userExists = $lock->get_result()->num_rows > 0;
        $lock->close();
        if (!$userExists) {
            $db->rollback();
            $transactionOpen = false;
            flash('error_flash', 'Your account cannot update saved internships right now.');
            redirect($redirectTarget);
        }

        $existing = $db->prepare('SELECT 1 FROM saved_internships WHERE user_id = ? AND internship_id = ? LIMIT 1');
        $existing->bind_param('ii', $userId, $internshipId);
        $existing->execute();
        $alreadySaved = $existing->get_result()->num_rows > 0;
        $existing->close();
        if ($alreadySaved) {
            $db->commit();
            $transactionOpen = false;
            flash('success_flash', 'This internship is already in your saved list.');
            redirect($redirectTarget);
        }

        $count = $db->prepare('SELECT COUNT(*) AS total FROM saved_internships WHERE user_id = ?');
        $count->bind_param('i', $userId);
        $count->execute();
        $savedCount = (int)$count->get_result()->fetch_assoc()['total'];
        $count->close();
        if ($savedCount >= 20) {
            $db->commit();
            $transactionOpen = false;
            flash('error_flash', 'Your shortlist can hold up to 20 internships. Remove one before saving another.');
            redirect($redirectTarget);
        }

        $statement = $db->prepare('INSERT INTO saved_internships (user_id, internship_id, saved_at) VALUES (?, ?, NOW())');
        $statement->bind_param('ii', $userId, $internshipId);
        $statement->execute();
        $statement->close();
        $db->commit();
        $transactionOpen = false;
        flash('success_flash', 'Internship saved to your list.');
    } else {
        $statement = $db->prepare('DELETE FROM saved_internships WHERE user_id = ? AND internship_id = ?');
        $statement->bind_param('ii', $userId, $internshipId);
        $statement->execute();
        $statement->close();
        flash('success_flash', 'Internship removed from your saved list.');
    }
} catch (mysqli_sql_exception $exception) {
    if ($transactionOpen) $db->rollback();
    error_log('BIT-InternshipHub saved internship update failed: '.$exception->getMessage());
    flash('error_flash', 'Your saved list could not be updated. Please try again.');
}

redirect($redirectTarget);

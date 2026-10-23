<?php
/**
 * ====================================================================
 * OSHDI VISIOPRO - API REST BACKEND DE GESTION DES RÉUNIONS (PHP 8.x)
 * ====================================================================
 * Endpoints :
 * - POST ?action=create         : Création d'une réunion (instantanée ou planifiée)
 * - GET  ?action=list           : Liste des réunions avec participants en direct
 * - GET  ?action=details        : Détails d'une réunion spécifique
 * - POST ?action=verify_access  : Vérification mot de passe et génération de jeton HMAC
 * - POST ?action=invite         : Création d'une invitation sécurisée
 * - POST ?action=end_meeting    : Clôture d'une réunion par l'organisateur
 * - GET  ?action=history        : Historique de présence des participants
 * - GET  ?action=audit_logs     : Journal d'audit pour les administrateurs
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/services/MeetingService.php';

// Initialisation du service métier
$meetingService = new MeetingService($pdo, $db_driver);
$auditService = new MeetingAuditService($pdo);

// Récupération des données de la requête
$action = $_REQUEST['action'] ?? 'list';
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true) ?: $_POST;

// Contexte utilisateur connecté
$currentUser = [
    'id'   => $_SESSION['user_id'] ?? null,
    'name' => $_SESSION['user_nom'] ?? ($input['user_name'] ?? 'Utilisateur OSHDI'),
    'role' => $_SESSION['user_role'] ?? ($input['user_role'] ?? 'participant')
];

// -------------------------------------------------------------
// 1. CRÉATION D'UNE RÉUNION (INSTANTANÉE OU PLANIFIÉE)
// -------------------------------------------------------------
if ($action === 'create') {
    try {
        $result = $meetingService->createMeeting($input, $currentUser);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 2. LISTE DES RÉUNIONS (POUR LE TABLEAU DE BORD)
// -------------------------------------------------------------
if ($action === 'list') {
    $limit  = intval($_GET['limit'] ?? 20);
    $status = !empty($_GET['status']) ? trim($_GET['status']) : null;

    $rooms = $meetingService->listMeetings($limit, $status);
    echo json_encode(['success' => true, 'rooms' => $rooms], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 3. DÉTAILS D'UNE RÉUNION
// -------------------------------------------------------------
if ($action === 'details') {
    $code = trim($_GET['code'] ?? ($_GET['meeting_code'] ?? ''));
    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $meeting = $meetingService->getMeeting($code);
    if ($meeting) {
        echo json_encode(['success' => true, 'meeting' => $meeting], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Réunion introuvable.']);
    }
    exit;
}

// -------------------------------------------------------------
// 4. VÉRIFICATION D'ACCÈS & DÉLIVRANCE DE JETON HMAC
// -------------------------------------------------------------
if ($action === 'verify_access') {
    $code     = trim($input['meeting_code'] ?? ($_REQUEST['meeting_code'] ?? ''));
    $userName = trim($input['user_name'] ?? $currentUser['name']);
    $password = $input['password'] ?? null;
    $token    = $input['token'] ?? null;
    $role     = $input['role'] ?? $currentUser['role'];

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $access = $meetingService->verifyMeetingAccess($code, $userName, $password, $token, $role, $currentUser['id']);

    if (!empty($access['allowed'])) {
        // Enregistrer l'entrée du participant dans l'historique
        $sessionId = $auditService->logParticipantJoin($code, $userName, $access['role'], $currentUser['id']);
        $access['participant_session_id'] = $sessionId;
        echo json_encode(array_merge(['success' => true], $access), JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(403);
        echo json_encode(array_merge(['success' => false], $access), JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// -------------------------------------------------------------
// 5. CRÉATION D'UNE INVITATION SÉCURISÉE
// -------------------------------------------------------------
if ($action === 'invite') {
    $code      = trim($input['meeting_code'] ?? '');
    $email     = trim($input['email'] ?? '');
    $recipName = trim($input['recipient_name'] ?? '');
    $role      = trim($input['role'] ?? 'participant');
    $expireHrs = intval($input['expires_in_hours'] ?? 72);

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $invitation = $meetingService->createInvitation($code, $email, $recipName, $role, $expireHrs);
    echo json_encode($invitation, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 6. CLÔTURE DE RÉUNION PAR L'ORGANISATEUR
// -------------------------------------------------------------
if ($action === 'end_meeting') {
    $code = trim($input['meeting_code'] ?? '');
    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $res = $meetingService->endMeeting($code, $currentUser['name']);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 7. HISTORIQUE DES PARTICIPANTS
// -------------------------------------------------------------
if ($action === 'history') {
    $code = trim($_GET['meeting_code'] ?? '');
    $history = $auditService->getMeetingParticipantsHistory($code, 50);
    echo json_encode(['success' => true, 'participants' => $history], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 8. JOURNAL D'AUDIT DE SÉCURITÉ (ADMINISTRATION)
// -------------------------------------------------------------
if ($action === 'audit_logs') {
    $code = !empty($_GET['meeting_code']) ? trim($_GET['meeting_code']) : null;
    $logs = $auditService->getMeetingAuditLogs($code, 50);
    echo json_encode(['success' => true, 'audit_logs' => $logs], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 9. MODIFICATION D'UNE RÉUNION (MODULE F)
// -------------------------------------------------------------
if ($action === 'update') {
    $code = trim($input['meeting_code'] ?? '');
    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $res = $meetingService->updateMeeting($code, $input, $currentUser['name']);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 10. ANNULATION D'UNE RÉUNION (MODULE F)
// -------------------------------------------------------------
if ($action === 'cancel') {
    $code = trim($input['meeting_code'] ?? '');
    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $res = $meetingService->cancelMeeting($code, $currentUser['name']);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 11. INVITATIONS PAR E-MAIL (MODULE F)
// -------------------------------------------------------------
if ($action === 'send_email_invite') {
    $code      = trim($input['meeting_code'] ?? '');
    $email     = trim($input['email'] ?? '');
    $recipName = trim($input['recipient_name'] ?? '');
    $role      = trim($input['role'] ?? 'participant');

    if (empty($code) || empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion et e-mail requis.']);
        exit;
    }

    $res = $meetingService->sendEmailInvitation($code, $email, $recipName, $role);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 12. LISTE DES INVITÉS & CONFIRMATIONS RSVP (MODULE F)
// -------------------------------------------------------------
if ($action === 'list_invitations') {
    $code = trim($_GET['meeting_code'] ?? '');
    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $invites = $meetingService->getMeetingInvitations($code);
    echo json_encode(['success' => true, 'invitations' => $invites], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 13. CONFIRMATION DE PRÉSENCE RSVP (MODULE F)
// -------------------------------------------------------------
if ($action === 'rsvp') {
    $token     = trim($input['token'] ?? '');
    $status    = trim($input['status'] ?? 'accepted');
    $guestName = !empty($input['guest_name']) ? trim($input['guest_name']) : null;

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Jeton d\'invitation requis.']);
        exit;
    }

    $res = $meetingService->updateRsvpStatus($token, $status, $guestName);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 14. INTÉGRATION CALENDRIER (GOOGLE, OUTLOOK, ICAL .ICS)
// -------------------------------------------------------------
if ($action === 'calendar_export') {
    $code   = trim($_GET['meeting_code'] ?? '');
    $format = trim($_GET['format'] ?? 'links');

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code de réunion requis.']);
        exit;
    }

    $meeting = $meetingService->getMeeting($code);
    if (!$meeting) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Réunion introuvable.']);
        exit;
    }

    $title = $meeting['title'] ?? 'Visioconférence OSHDI VisioPro';
    $desc = ($meeting['description'] ?: 'Visioconférence pastorale et formation OSHDI.') . "\n\nLien de connexion direct : " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/classroom.php?room=' . urlencode($code);
    $location = 'Visioconférence OSHDI VisioPro en ligne';

    $startTs = !empty($meeting['scheduled_start']) ? strtotime($meeting['scheduled_start']) : time() + 3600;
    $endTs   = !empty($meeting['scheduled_end']) ? strtotime($meeting['scheduled_end']) : ($startTs + 3600);

    $dtStartUtc = gmdate('Ymd\THis\Z', $startTs);
    $dtEndUtc   = gmdate('Ymd\THis\Z', $endTs);

    // Si téléchargement direct .ics
    if ($format === 'ics') {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="oshdi-' . strtolower($code) . '.ics"');

        $rrule = '';
        if (!empty($meeting['recurrence']) && $meeting['recurrence'] !== 'none') {
            $freqMap = ['daily' => 'DAILY', 'weekly' => 'WEEKLY', 'monthly' => 'MONTHLY'];
            if (isset($freqMap[$meeting['recurrence']])) {
                $rrule = "RRULE:FREQ=" . $freqMap[$meeting['recurrence']] . "\r\n";
            }
        }

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//OSHDI//VisioPro//FR\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "BEGIN:VEVENT\r\n";
        echo "UID:oshdi-meeting-{$code}@oshdi.org\r\n";
        echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        echo "DTSTART:{$dtStartUtc}\r\n";
        echo "DTEND:{$dtEndUtc}\r\n";
        if ($rrule) echo $rrule;
        echo "SUMMARY:" . addcslashes($title, ",;\\") . "\r\n";
        echo "DESCRIPTION:" . addcslashes($desc, ",;\\") . "\r\n";
        echo "LOCATION:" . addcslashes($location, ",;\\") . "\r\n";
        echo "STATUS:CONFIRMED\r\n";
        echo "END:VEVENT\r\n";
        echo "END:VCALENDAR\r\n";
        exit;
    }

    // Liens web pour Google Calendar et Outlook
    $googleUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE"
        . "&text=" . urlencode($title)
        . "&dates=" . $dtStartUtc . "/" . $dtEndUtc
        . "&details=" . urlencode($desc)
        . "&location=" . urlencode($location);

    $outlookUrl = "https://outlook.live.com/calendar/0/deeplink/compose?"
        . "subject=" . urlencode($title)
        . "&startdt=" . urlencode(date('Y-m-d\TH:i:s', $startTs))
        . "&enddt=" . urlencode(date('Y-m-d\TH:i:s', $endTs))
        . "&body=" . urlencode($desc)
        . "&location=" . urlencode($location);

    $icsDownloadUrl = "api/classroom_meetings.php?action=calendar_export&meeting_code=" . urlencode($code) . "&format=ics";

    echo json_encode([
        'success'           => true,
        'meeting_code'      => $code,
        'title'             => $title,
        'scheduled_start'   => $meeting['scheduled_start'],
        'timezone'          => $meeting['timezone'] ?? 'Africa/Abidjan',
        'recurrence'        => $meeting['recurrence'] ?? 'none',
        'google_calendar'   => $googleUrl,
        'outlook_calendar'  => $outlookUrl,
        'ics_download_url'  => $icsDownloadUrl
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Action inconnue']);

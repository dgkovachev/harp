<?php
namespace App;
use PDO;
class AnnouncementHandler extends PDO_CON{
    private $tokenService;

    public function __construct($tokenService){
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    private function requireAuth(){
        $token = $this->tokenService->extractToken();
        if (!$this->tokenService->authenticate($token)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    }

    public function insertAnnouncement(array $params){
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $this->tokenService->getCurrentUser();

        $category = $data['category'] ?? 'general';
        $clubId = isset($data['club_id']) && $data['club_id'] !== '' ? $data['club_id'] : null;
        $eventId = isset($data['event_id']) && $data['event_id'] !== '' ? $data['event_id'] : null;

        if ($category === 'club' && empty($clubId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Club ID is required for club announcements']);
            return;
        }
        if ($category === 'event' && empty($eventId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Event ID is required for event announcements']);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO announcements (school_id, club_id, event_id, created_by, title, body, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['school_id'] ?? $user['school_id'],
            $clubId,
            $eventId,
            $user['user_id'],
            $data['title'],
            $data['body'],
            $category
        ]);
        if($stmt->rowCount() > 0) echo json_encode(['success' => true, 'message'=> 'Created announcement successfully']);
        else echo json_encode(['success' => false, 'message'=> 'Failed to create announcement', "user" => $user]);

    }

    public function getAnnouncementByID(array $params){
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();
        $stmt = $this->pdo->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
        $stmt->execute([$params['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stmt2 = $this->pdo->prepare("INSERT IGNORE INTO user_announcement_read (user_id, announcement_id, read_at) VALUES (?, ?, NOW())");
            $stmt2->execute([$user['user_id'], $row['announcement_id']]);
            echo json_encode(['success' => true, 'data' => $row]);
        } else echo json_encode(['success' => false, 'message' => 'Announcement not found']);
    }

    public function getAllAnnouncements(array $params){
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();
        $school = $user['school_id'] ?? 0;
        $userId = $user['user_id'];
        $role = $user['role'] ?? 'student';

        if ($role === 'organizer') {
            $stmt = $this->pdo->prepare(
                "SELECT a.*, (uar.user_id IS NOT NULL) AS is_read FROM announcements a
                 LEFT JOIN user_announcement_read uar ON uar.announcement_id = a.announcement_id AND uar.user_id = ?
                 WHERE a.school_id = ? ORDER BY a.created_at DESC"
            );
            $stmt->execute([$userId, $school]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT a.*, (uar.user_id IS NOT NULL) AS is_read FROM announcements a
                 LEFT JOIN user_announcement_read uar ON uar.announcement_id = a.announcement_id AND uar.user_id = ?
                 LEFT JOIN club_members cm ON a.club_id = cm.club_id AND cm.user_id = ?
                 WHERE a.school_id = ?
                   AND (a.category != 'club' OR cm.user_id IS NOT NULL)
                 ORDER BY a.created_at DESC"
            );
            $stmt->execute([$userId, $userId, $school]);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getAnnouncementByTitle(array $params){
        $this->requireAuth();
        $stmt = $this->pdo->prepare("SELECT * FROM announcements WHERE LOWER(title) LIKE LOWER(?) ORDER BY created_at DESC");
        $stmt->execute(['%' . $params['title'] . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function markAnnouncementAsRead(array $params){
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO user_announcement_read (user_id, announcement_id, read_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user['user_id'], $params['id']]);
        echo json_encode(['success' => true]);
    }

    public function updateAnnouncement(array $params){
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("UPDATE announcements SET title = COALESCE(?, title), body = COALESCE(?, body), category = COALESCE(?, category) WHERE announcement_id = ?");
        $stmt->execute([
            $data['title'] ?? null,
            $data['body'] ?? null,
            $data['category'] ?? null,
            $params['id']
        ]);
        if($stmt->rowCount() > 0) echo json_encode(['success' => true, 'message'=> 'Updated announcement successfully']);
        else echo json_encode(['success' => false, 'message'=> 'No changes made']);
    }

    public function deleteAnnouncement(array $params){
        $this->requireAuth();
        $stmt = $this->pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        $stmt->execute([$params['id']]);
        if($stmt->rowCount() > 0) echo json_encode(['success' => true, 'message'=> 'Deleted announcement successfully']);
        else echo json_encode(['success' => false, 'message'=> 'Announcement not found']);
    }
}
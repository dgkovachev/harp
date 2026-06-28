<?php
namespace App;
use PDO;
class AnnouncementHandler extends PDO_CON{
    public function __construct(){
        if(!isset($this->pdo))parent::__construct();
    }

    public function insertAnnouncement(array $params){
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("INSERT INTO announcements (school_id, club_id, created_by, title, body, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['school_id'],
            $data['club_id'] ?? null,
            $data['created_by'],
            $data['title'],
            $data['body'],
            $data['category']
        ]);
        if($stmt->rowCount() > 0) echo json_encode(['success' => true, 'message'=> 'Created announcement successfully']);
        else echo json_encode(['success' => false, 'message'=> 'Failed to create announcement']);

    }

    public function getAnnouncementByID(array $params){
        $stmt = $this->pdo->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
        $stmt->execute([$params['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) echo json_encode(['success' => true, 'data' => $row]);
        else echo json_encode(['success' => false, 'message' => 'Announcement not found']);
    }

    public function getAllAnnouncements(array $params){
        $stmt = $this->pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getAnnouncementByTitle(array $params){
        $stmt = $this->pdo->prepare("SELECT * FROM announcements WHERE LOWER(title) LIKE LOWER(?) ORDER BY created_at DESC");
        $stmt->execute(['%' . $params['title'] . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function updateAnnouncement(array $params){
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("UPDATE announcements SET school_id = ?, club_id = ?, title = ?, body = ?, category = ? WHERE announcement_id = ?");
        $stmt->execute([
            $data['school_id'] ?? null,
            $data['club_id'] ?? null,
            $data['title'] ?? null,
            $data['body'] ?? null,
            $data['category'] ?? null,
            $params['id']
        ]);
        if($stmt->rowCount() > 0) echo json_encode(['success' => true, 'message'=> 'Updated announcement successfully']);
        else echo json_encode(['success' => false, 'message'=> 'No changes made']);
    }

    public function deleteAnnouncement(array $params){
        $stmt = $this->pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        $stmt->execute([$params['id']]);
        if($stmt->rowCount() > 0) echo json_encode(['success' => true, 'message'=> 'Deleted announcement successfully']);
        else echo json_encode(['success' => false, 'message'=> 'Announcement not found']);
    }
}
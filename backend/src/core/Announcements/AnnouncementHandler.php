<?php
namespace App;
class AnnouncementHandler extends PDO_CON{
    public function __construct(){
        if(!isset($this->pdo))parent::__construct();
    }

    public function insertAnnouncement(array $data){
        $school_id = $data["school_id"];
        $club_id = $data["club_id"];
        $created_by = $data["created_by"];
        $title = $data["title"];
        $body = $data["body"];
        $category = $data["category"];
        $stmt = $this->pdo->prepare("INSERT INTO announcements (school_id, club_id, created_by, title, body, category, expires_at)
                            VALUES ?, ?, ?, ?, ?, ?");
        $stmt->execute([
            $school_id,
            $club_id,
            $created_by,
            $title,
            $body,
            $category
        ]);
        if($stmt->rowCount() > 0) print(json_encode(['success' => false, 'message'=> 'Created announcement unsucccesfuly']));
        else print(json_encode(['success' => true, 'message'=> 'Created announcement succcesfuly']));

    }
}
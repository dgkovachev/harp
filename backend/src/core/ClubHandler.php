<?php
namespace App;

use PDO;

class ClubHandler extends PDO_CON
{
    private $tokenService;

    public function __construct($tokenService)
    {
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    private function requireAuth()
    {
        $token = $this->tokenService->extractToken();
        if (!$this->tokenService->authenticate($token)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    }

    private function requireRole($role)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();
        if ($user['role'] !== $role) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }
    }

    public function createClub(array $params)
    {
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT club_id FROM clubs WHERE created_by = ?");
        $stmt->execute([$user['user_id']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'You already own a club']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT club_id FROM clubs WHERE club_name = ?");
        $stmt->execute([$data['club_name']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Club name already taken']);
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO clubs (school_domain, created_by, club_name, description, logo_url, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $data['school_id'] ?? $user['school_id'],
            $user['user_id'],
            $data['club_name'],
            $data['description'] ?? null,
            $data['logo_url'] ?? null,
        ]);
        $clubId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO club_members (user_id, club_id) VALUES (?, ?)");
        $stmt->execute([$user['user_id'], $clubId]);

        $stmt = $this->pdo->prepare("UPDATE clubs SET member_count = member_count + 1 WHERE club_id = ?");
        $stmt->execute([$clubId]);

        echo json_encode(['success' => true, 'club_id' => $clubId]);
    }

    public function updateClub(array $params)
    {
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$club || $club['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        if (isset($data['club_name']) && $data['club_name'] !== $club['club_name']) {
            $stmt = $this->pdo->prepare("SELECT club_id FROM clubs WHERE club_name = ? AND club_id != ?");
            $stmt->execute([$data['club_name'], $params['id']]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => 'Club name already taken']);
                return;
            }
        }

        $stmt = $this->pdo->prepare(
            "UPDATE clubs SET club_name = ?, description = ?, logo_url = ? WHERE club_id = ?"
        );
        $stmt->execute([
            $data['club_name'] ?? $club['club_name'],
            $data['description'] ?? $club['description'],
            $data['logo_url'] ?? $club['logo_url'],
            $params['id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Club updated']);
    }

    public function deleteClub(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$club || $club['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("DELETE FROM club_members WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $stmt = $this->pdo->prepare("DELETE FROM clubs WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $this->pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Club deleted']);
    }

    public function approveClub(array $params)
    {
        $this->requireRole('organizer');
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$club) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Club not found']);
            return;
        }
        if ($club['is_approved']) {
            echo json_encode(['success' => true, 'message' => 'Club already approved']);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE clubs SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE club_id = ?");
        $stmt->execute([$user['user_id'], $params['id']]);
        echo json_encode(['success' => true, 'message' => 'Club approved']);
    }

    public function listClubs(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        if ($user['role'] === 'organizer') {
            $stmt = $this->pdo->query("SELECT c.*, u.display_name AS owner_name FROM clubs c JOIN users u ON c.created_by = u.user_id ORDER BY c.created_at DESC");
        } else {
            $stmt = $this->pdo->query("SELECT c.*, u.display_name AS owner_name FROM clubs c JOIN users u ON c.created_by = u.user_id WHERE c.is_approved = 1 ORDER BY c.created_at DESC");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getClub(array $params)
    {
        $this->requireAuth();
        $stmt = $this->pdo->prepare("SELECT c.*, u.display_name AS owner_name FROM clubs c JOIN users u ON c.created_by = u.user_id WHERE c.club_id = ?");
        $stmt->execute([$params['id']]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$club) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Club not found']);
            return;
        }

        $user = $this->tokenService->getCurrentUser();
        if ($user['role'] !== 'organizer' && !$club['is_approved']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Club not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $club]);
    }

    public function joinClub(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$club || !$club['is_approved']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Club not found']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT 1 FROM club_members WHERE user_id = ? AND club_id = ?");
        $stmt->execute([$user['user_id'], $params['id']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Already a member']);
            return;
        }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("INSERT INTO club_members (user_id, club_id) VALUES (?, ?)");
        $stmt->execute([$user['user_id'], $params['id']]);
        $stmt = $this->pdo->prepare("UPDATE clubs SET member_count = member_count + 1 WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $this->pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Joined club']);
    }

    public function leaveClub(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM club_members WHERE user_id = ? AND club_id = ?");
        $stmt->execute([$user['user_id'], $params['id']]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Not a member']);
            return;
        }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("DELETE FROM club_members WHERE user_id = ? AND club_id = ?");
        $stmt->execute([$user['user_id'], $params['id']]);
        $stmt = $this->pdo->prepare("UPDATE clubs SET member_count = GREATEST(member_count - 1, 0) WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $this->pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Left club']);
    }

    public function getClubMembers(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
        $stmt->execute([$params['id']]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$club) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Club not found']);
            return;
        }
        if ($user['role'] !== 'organizer' && $club['created_by'] != $user['user_id'] && !$club['is_approved']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT cm.user_id, cm.joined_at, u.display_name, u.user_email 
            FROM club_members cm JOIN users u ON cm.user_id = u.user_id 
            WHERE cm.club_id = ? ORDER BY cm.joined_at ASC");
        $stmt->execute([$params['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getMyClubs(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT c.*, u.display_name AS owner_name 
            FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id 
            JOIN users u ON c.created_by = u.user_id 
            WHERE cm.user_id = ? AND c.is_approved = 1 
            ORDER BY c.club_name ASC");
        $stmt->execute([$user['user_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getUnapprovedClubs(array $params)
    {
        $this->requireRole('organizer');
        $stmt = $this->pdo->query("SELECT c.*, u.display_name AS owner_name FROM clubs c JOIN users u ON c.created_by = u.user_id WHERE c.is_approved = 0 ORDER BY c.created_at ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }
}

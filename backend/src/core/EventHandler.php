<?php
namespace App;

use PDO;

class EventHandler extends PDO_CON
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

    public function createEvent(array $params)
    {
        $this->requireRole('organizer');
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare(
            "INSERT INTO events (created_by, school_id, club_id, title, description, event_date, location, max_capacity)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $user['user_id'],
            $data['school_id'] ?? $user['school_id'] ?? null,
            $data['club_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            $data['event_date'],
            $data['location'] ?? null,
            $data['max_capacity'],
        ]);
        echo json_encode(['success' => true, 'event_id' => (int)$this->pdo->lastInsertId()]);
    }

    public function updateEvent(array $params)
    {
        $this->requireRole('organizer');
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$params['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event || $event['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, max_capacity = ?, club_id = ? WHERE event_id = ?"
        );
        $stmt->execute([
            $data['title'] ?? $event['title'],
            $data['description'] ?? $event['description'],
            $data['event_date'] ?? $event['event_date'],
            $data['location'] ?? $event['location'],
            $data['max_capacity'] ?? $event['max_capacity'],
            $data['club_id'] ?? $event['club_id'],
            $params['id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Event updated']);
    }

    public function cancelEvent(array $params)
    {
        $this->requireRole('organizer');
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$params['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event || $event['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("DELETE FROM registrations WHERE event_id = ?");
        $stmt->execute([$params['id']]);
        $stmt = $this->pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->execute([$params['id']]);
        $this->pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Event cancelled and deleted']);
    }

    public function listEvents(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        if ($user['role'] === 'organizer') {
            $stmt = $this->pdo->prepare("SELECT e.*, 
                (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed') AS confirmed_count, 
                (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'waitlisted') AS waitlist_count 
                FROM events e WHERE e.created_by = ? ORDER BY e.created_at DESC");
            $stmt->execute([$user['user_id']]);
        } else {
            $stmt = $this->pdo->query("SELECT e.*, 
                (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed') AS confirmed_count, 
                (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'waitlisted') AS waitlist_count 
                FROM events e ORDER BY e.event_date ASC");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getEvent(array $params)
    {
        $this->requireAuth();
        $stmt = $this->pdo->prepare("SELECT e.*, 
            (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed') AS confirmed_count, 
            (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'waitlisted') AS waitlist_count 
            FROM events e WHERE e.event_id = ?");
        $stmt->execute([$params['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Event not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $event]);
    }

    public function registerForEvent(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();
        $eventId = $params['id'];

        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Event not found']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT registration_id, status FROM registrations WHERE event_id = ? AND user_id = ? AND status IN ('confirmed', 'waitlisted')");
        $stmt->execute([$eventId, $user['user_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Already registered with status: ' . $existing['status']]);
            return;
        }

        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM registrations WHERE event_id = ? AND status = 'confirmed' FOR UPDATE");
        $stmt->execute([$eventId]);
        $confirmed = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

        $stmt = $this->pdo->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND user_id = ? AND status = 'cancelled'");
        $stmt->execute([$eventId, $user['user_id']]);
        $cancelled = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($confirmed < $event['max_capacity']) {
            if ($cancelled) {
                $stmt = $this->pdo->prepare("UPDATE registrations SET status = 'confirmed', queue_position = NULL WHERE registration_id = ?");
                $stmt->execute([$cancelled['registration_id']]);
                $regId = $cancelled['registration_id'];
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'confirmed')");
                $stmt->execute([$eventId, $user['user_id']]);
                $regId = (int)$this->pdo->lastInsertId();
            }
            $this->pdo->commit();
            echo json_encode(['success' => true, 'registration_id' => $regId, 'status' => 'confirmed']);
        } else {
            $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(queue_position), 0) + 1 AS next_pos FROM registrations WHERE event_id = ? AND status = 'waitlisted'");
            $stmt->execute([$eventId]);
            $nextPos = (int)$stmt->fetch(PDO::FETCH_ASSOC)['next_pos'];
            if ($cancelled) {
                $stmt = $this->pdo->prepare("UPDATE registrations SET status = 'waitlisted', queue_position = ? WHERE registration_id = ?");
                $stmt->execute([$nextPos, $cancelled['registration_id']]);
                $regId = $cancelled['registration_id'];
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO registrations (event_id, user_id, status, queue_position) VALUES (?, ?, 'waitlisted', ?)");
                $stmt->execute([$eventId, $user['user_id'], $nextPos]);
                $regId = (int)$this->pdo->lastInsertId();
            }
            $this->pdo->commit();
            echo json_encode(['success' => true, 'registration_id' => $regId, 'status' => 'waitlisted', 'queue_position' => $nextPos]);
        }
    }

    public function cancelRegistration(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT r.*, e.created_by FROM registrations r JOIN events e ON r.event_id = e.event_id WHERE r.registration_id = ?");
        $stmt->execute([$params['id']]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            return;
        }
        if ($reg['user_id'] != $user['user_id'] && $reg['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }
        if (!in_array($reg['status'], ['confirmed', 'waitlisted'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Registration is already cancelled']);
            return;
        }

        $wasConfirmed = ($reg['status'] === 'confirmed');
        $eventId = $reg['event_id'];

        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("UPDATE registrations SET status = 'cancelled', queue_position = NULL WHERE registration_id = ?");
        $stmt->execute([$params['id']]);

        if ($wasConfirmed) {
            $stmt = $this->pdo->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND status = 'waitlisted' ORDER BY queue_position ASC LIMIT 1 FOR UPDATE");
            $stmt->execute([$eventId]);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($next) {
                $stmt = $this->pdo->prepare("UPDATE registrations SET status = 'confirmed', queue_position = NULL WHERE registration_id = ?");
                $stmt->execute([$next['registration_id']]);
            }
        }

        $this->pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Registration cancelled']);
    }

    public function getMyRegistrations(array $params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT r.*, e.title, e.event_date, e.location 
            FROM registrations r JOIN events e ON r.event_id = e.event_id 
            WHERE r.user_id = ? AND r.status IN ('confirmed', 'waitlisted') 
            ORDER BY e.event_date ASC");
        $stmt->execute([$user['user_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getEventRegistrations(array $params)
    {
        $this->requireRole('organizer');
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$params['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event || $event['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT r.*, u.display_name, u.user_email 
            FROM registrations r JOIN users u ON r.user_id = u.user_id 
            WHERE r.event_id = ? AND r.status = 'confirmed' 
            ORDER BY r.registered_at ASC");
        $stmt->execute([$params['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function getEventWaitlist(array $params)
    {
        $this->requireRole('organizer');
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$params['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event || $event['created_by'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT r.*, u.display_name, u.user_email 
            FROM registrations r JOIN users u ON r.user_id = u.user_id 
            WHERE r.event_id = ? AND r.status = 'waitlisted' 
            ORDER BY r.queue_position ASC");
        $stmt->execute([$params['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }
}

<?php

$router->post('/login', [$auth, 'login']);
$router->post('/register', [$auth, 'register']);
$router->post('/logout', [$auth, 'logout']);
$router->get('/users/{id}', [$auth, 'getUser']);
$router->put('/users/{id}', [$auth, 'updateUser']);
$router->delete('/users/{id}', [$auth, 'deleteUser']);
$router->post('/check-domain/{domain}', [$auth, 'checkDomain']);
$router->get('/verify', [$auth, 'verifyUser']);
$router->post('/verify', [$auth, 'verifyUser']);
$router->post('/resend-verification', [$auth, 'resendVerification']);

$router->post('/createAnnouncement', [$AnnouncementHandler, 'insertAnnouncement']);
$router->get('/announcement/{id}', [$AnnouncementHandler, 'getAnnouncementByID']);
$router->get('/announcements', [$AnnouncementHandler, 'getAllAnnouncements']);
$router->get('/announcements/search/{title}', [$AnnouncementHandler, 'getAnnouncementByTitle']);
$router->put('/announcement/{id}', [$AnnouncementHandler, 'updateAnnouncement']);
$router->delete('/announcement/{id}', [$AnnouncementHandler, 'deleteAnnouncement']);

$router->post('/clubs', [$ClubHandler, 'createClub']);
$router->put('/clubs/{id}', [$ClubHandler, 'updateClub']);
$router->delete('/clubs/{id}', [$ClubHandler, 'deleteClub']);
$router->post('/clubs/{id}/approve', [$ClubHandler, 'approveClub']);
$router->get('/clubs/unapproved', [$ClubHandler, 'getUnapprovedClubs']);
$router->get('/clubs/me', [$ClubHandler, 'getMyClubs']);
$router->get('/clubs/unapproved', [$ClubHandler, 'getUnapprovedClubs']);
$router->get('/clubs', [$ClubHandler, 'listClubs']);
$router->get('/clubs/{id}/members', [$ClubHandler, 'getClubMembers']);
$router->get('/clubs/{id}', [$ClubHandler, 'getClub']);
$router->post('/clubs/{id}/join', [$ClubHandler, 'joinClub']);
$router->delete('/clubs/{id}/leave', [$ClubHandler, 'leaveClub']);

$router->post('/events', [$EventHandler, 'createEvent']);
$router->put('/events/{id}', [$EventHandler, 'updateEvent']);
$router->post('/events/{id}/cancel', [$EventHandler, 'cancelEvent']);
$router->post('/events/{id}/register', [$EventHandler, 'registerForEvent']);
$router->get('/events/{id}/registrations', [$EventHandler, 'getEventRegistrations']);
$router->get('/events/{id}/waitlist', [$EventHandler, 'getEventWaitlist']);
$router->get('/events', [$EventHandler, 'listEvents']);
$router->get('/events/{id}', [$EventHandler, 'getEvent']);
$router->delete('/registrations/{id}', [$EventHandler, 'cancelRegistration']);
$router->get('/registrations/me', [$EventHandler, 'getMyRegistrations']);
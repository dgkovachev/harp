<?php

$router->post('/login', [$auth, 'login']);
$router->post('/register', [$auth, 'register']);
$router->post('/logout', [$auth, 'logout']);
$router->get('/users/{id}', [$auth, 'getUser']);
$router->put('/users/{id}', [$auth, 'updateUser']);
$router->delete('/users/{id}', [$auth, 'deleteUser']);
$router->post('/check-domain/{domain}', [$auth, 'checkDomain']);

$router->post('/createAnnouncement', [$AnnouncementHandler, 'insertAnnouncement']);
$router->get('/announcement/{id}', [$AnnouncementHandler, 'getAnnouncementByID']);
$router->get('/announcements', [$AnnouncementHandler, 'getAllAnnouncements']);
$router->get('/announcements/search/{title}', [$AnnouncementHandler, 'getAnnouncementByTitle']);
$router->put('/announcement/{id}', [$AnnouncementHandler, 'updateAnnouncement']);
$router->delete('/announcement/{id}', [$AnnouncementHandler, 'deleteAnnouncement']);
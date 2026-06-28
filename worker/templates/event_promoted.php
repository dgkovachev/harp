<?php
function renderEventPromotedEmail($name, $eventTitle, $eventDate, $location)
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: white; border-radius: 8px; padding: 30px;">
<h2 style="color: #28a745;">A spot opened up, {$name}! 🎉</h2>
<p style="font-size: 16px;">You have been promoted from the waitlist for <strong>{$eventTitle}</strong>.</p>
<table style="font-size: 14px; color: #555; margin: 20px 0;">
<tr><td style="padding: 4px 12px 4px 0;"><strong>Date:</strong></td><td>{$eventDate}</td></tr>
<tr><td style="padding: 4px 12px 4px 0;"><strong>Location:</strong></td><td>{$location}</td></tr>
</table>
<p style="font-size: 14px; color: #777;">See you there!</p>
</div>
</body>
</html>
HTML;
}
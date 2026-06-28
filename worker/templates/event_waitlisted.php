<?php
function renderEventWaitlistedEmail($name, $eventTitle, $position)
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: white; border-radius: 8px; padding: 30px;">
<h2 style="color: #333;">You're on the waitlist, {$name}</h2>
<p style="font-size: 16px;">The event <strong>{$eventTitle}</strong> is full. You are <strong>#{$position}</strong> on the waitlist.</p>
<p style="font-size: 14px; color: #777;">We'll notify you if a spot opens up.</p>
</div>
</body>
</html>
HTML;
}
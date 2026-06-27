<?php
// Variables available from the worker: $name

return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Welcome to HARP!</title>
</head>
<body style='margin:0; padding:0; background:#f6f9fc; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f6f9fc; padding:40px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); padding:40px;'>
                    <tr>
                        <td>
                            <!-- Header -->
                            <h1 style='margin:0 0 10px 0; font-size:28px; color:#1a2b3c; text-align:center;'>
                                🏫 HARP
                            </h1>
                            <p style='text-align:center; color:#6b7a8a; font-size:16px; margin-top:0;'>
                                Hub for Academic Resources and Planning
                            </p>
                            <hr style='border:0; border-top:2px solid #e8edf2; margin:25px 0;'>

                            <!-- Content -->
                            <h2 style='color:#1a2b3c; font-size:22px; margin:0 0 15px 0;'>
                                Welcome to the Community, {$name}! 🎉
                            </h2>
                            <p style='color:#3d4a5a; font-size:16px; line-height:1.6; margin:0 0 15px 0;'>
                                Your account is now active. You are officially part of the <strong>HARP</strong> ecosystem – your central hub for academic resources, event notifications, and campus planning.
                            </p>

                            <h3 style='color:#1a2b3c; font-size:18px; margin:25px 0 10px 0;'>
                                What you can do next:
                            </h3>
                            <ul style='color:#3d4a5a; font-size:16px; line-height:1.8; padding-left:20px; margin:0 0 25px 0;'>
                                <li>📅 Browse upcoming school events and workshops</li>
                                <li>📚 Access shared academic resources and study materials</li>
                                <li>🔔 Get real-time notifications for important announcements</li>
                                <li>👥 Connect with peers and instructors</li>
                            </ul>

                            <!-- Call to Action Button -->
                            <table width='100%' cellpadding='0' cellspacing='0' style='margin:30px 0;'>
                                <tr>
                                    <td align='center'>
                                        <a href='https://harp.smartech.bg/dashboard' 
                                           style='display:inline-block; background:#22c55e; color:#ffffff; font-weight:600; padding:14px 40px; border-radius:6px; text-decoration:none; font-size:16px;'>
                                            🚀 Go to Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style='color:#6b7a8a; font-size:14px; line-height:1.6; margin:0 0 5px 0;'>
                                Have questions? Just reply to this email and our team will help you out.
                            </p>
                            <p style='color:#1a2b3c; font-size:16px; font-weight:600; margin:15px 0 0 0;'>
                                Cheers,<br>
                                The HARP Team
                            </p>

                            <hr style='border:0; border-top:1px solid #e8edf2; margin:30px 0 15px 0;'>
                            <p style='color:#9aa9b9; font-size:12px; text-align:center; margin:0;'>
                                &copy; 2026 HARP – AIBEST Tech Academy
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
";
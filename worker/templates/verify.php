<?php
function renderVerifyEmail($name, $verifyLink) {
    $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $linkEsc = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verify Your HARP Account</title>
</head>
<body style='margin:0; padding:0; background:#f6f9fc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f6f9fc; padding:40px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); padding:40px;'>
                    <tr>
                        <td>
                            <h1 style='margin:0 0 10px 0; font-size:28px; color:#1a2b3c; text-align:center;'>
                                HARP
                            </h1>
                            <p style='text-align:center; color:#6b7a8a; font-size:16px; margin-top:0;'>
                                Hub for Academic Resources and Planning
                            </p>
                            <hr style='border:0; border-top:2px solid #e8edf2; margin:25px 0;'>

                            <h2 style='color:#1a2b3c; font-size:22px; margin:0 0 15px 0;'>
                                Hello, {$nameEsc}!
                            </h2>
                            <p style='color:#3d4a5a; font-size:16px; line-height:1.6; margin:0 0 20px 0;'>
                                Thanks for signing up for <strong>HARP</strong>. To get started, please verify your email address by clicking the button below.
                            </p>

                            <table width='100%' cellpadding='0' cellspacing='0' style='margin:30px 0;'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$linkEsc}' 
                                           style='display:inline-block; background:#4f46e5; color:#ffffff; font-weight:600; padding:14px 40px; border-radius:6px; text-decoration:none; font-size:16px;'>
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style='color:#6b7a8a; font-size:14px; line-height:1.6; margin:0 0 5px 0;'>
                                If you didn't create an account, you can safely ignore this email.
                            </p>
                            <p style='color:#6b7a8a; font-size:14px; line-height:1.6; margin:0;'>
                                This link expires in <strong>24 hours</strong>.
                            </p>

                            <hr style='border:0; border-top:1px solid #e8edf2; margin:30px 0 15px 0;'>
                            <p style='color:#9aa9b9; font-size:12px; text-align:center; margin:0 0 10px 0;'>
                                <a href='https://www.google.com' style='color:#4f46e5;'>Search on Google</a>
                            </p>
                            <p style='color:#9aa9b9; font-size:12px; text-align:center; margin:0;'>
                                &copy; 2026 HARP &ndash; AIBEST Tech Academy
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

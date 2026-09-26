<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply from BSSShop</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .header { background: linear-gradient(90deg, #00dafb 0%, #000113 100%); color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; color: #333; line-height: 1.6; }
        .reply-box { background: #f8f9fc; border-left: 4px solid #00dafb; padding: 16px; margin: 16px 0; border-radius: 4px; white-space: pre-wrap; }
        .footer { background: #f8f9fc; padding: 16px 24px; text-align: center; color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BSSShop Support</h1>
        </div>
        <div class="body">
            <p>Hi {{ $name }},</p>
            <p>Thank you for contacting us. Here is our response to your inquiry{{ $subject ? ' regarding "' . $subject . '"' : '' }}:</p>

            <div class="reply-box">{!! nl2br(e($reply)) !!}</div>

            <p>If you have any further questions, feel free to reply to this email.</p>
            <p>Best regards,<br>The BSSShop Team</p>
        </div>
        <div class="footer">
            &copy; {{ $year }} BSSShop. All rights reserved.
        </div>
    </div>
</body>
</html>
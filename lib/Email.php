<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    const RESEND_COOLDOWN = 60; // 60 seconds
    private static function getMailer()
    {
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = SMTP_DEBUG;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        return $mail;
    }

    public static function sendPasswordReset($email, $token)
    {
        $mail = self::getMailer();
        $mail->addAddress($email);
        $mail->Subject = 'Password Reset - BlindeDoos';

        $resetLink = 'http://localhost' . BASE_URL . 'views/pages/reset_password.php?token=' . $token;

        $mail->Body = "
        <h2>Password Reset Request</h2>
        <p>Click the link below to reset your password:</p>
        <p><a href='{$resetLink}' style='display:inline-block;padding:10px 20px;background:#3b82f6;color:white;text-decoration:none;border-radius:4px;'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request this, please ignore this email.</p>
        ";
        $mail->isHTML(true);

        return $mail->send();
    }

    public static function sendAdminPasswordReset($email, $fullname, $resetLink)
    {
        $mail = self::getMailer();
        $mail->addAddress($email, $fullname);
        $mail->Subject = 'Password Reset - BlindeDoos Admin';

        $mail->Body = "
        <h2>Admin Password Reset Request</h2>
        <p>Hello {$fullname},</p>
        <p>We received a request to reset your admin account password.</p>
        <p>Click the link below to reset your password:</p>
        <p><a href='{$resetLink}' style='display:inline-block;padding:10px 20px;background:#3b82f6;color:white;text-decoration:none;border-radius:4px;'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p><strong>Security Notice:</strong> If you didn't request this password reset, please ignore this email and contact your system administrator immediately.</p>
        <br>
        <p style='color:#6b7280;font-size:0.875rem;'>BlindeDoos Admin Portal</p>
        ";
        $mail->isHTML(true);

        return $mail->send();
    }

    public static function sendEmailVerification($email, $token)
    {
        $mail = self::getMailer();
        $mail->addAddress($email);
        $mail->Subject = 'Verify Your Email - BlindeDoos';

        $verifyLink = 'http://localhost' . BASE_URL . 'controllers/AuthController.php?action=verify_email&token=' . $token;

        $mail->Body = "
        <h2>Welcome to BlindeDoos!</h2>
        <p>Please verify your email address by clicking the link below:</p>
        <p><a href='{$verifyLink}' style='display:inline-block;padding:10px 20px;background:#3b82f6;color:white;text-decoration:none;border-radius:4px;'>Verify Email</a></p>
        <p>This link will expire in 24 hours.</p>
        <p>If you didn't create an account, please ignore this email.</p>
        ";
        $mail->isHTML(true);

        return $mail->send();
    }

    public static function sendOrderConfirmation($email, $orderItems, $order, $userData, $isShipping, $date, $time, $qrCodePath = null)
    {
        $mail = self::getMailer();
        $mail->addAddress($email);
        $mail->addEmbeddedImage(__DIR__ . '/../assets/images/uploads/logo.png', 'logo_cid', 'logo.png');
        
        // Embed QR code if provided
        if ($qrCodePath && file_exists($qrCodePath)) {
            $mail->addEmbeddedImage($qrCodePath, 'qr_code_cid', basename($qrCodePath));
        }
        
        $mail->Subject = "Order Confirmation for #" . htmlspecialchars($order['order_id']);

        // Format payment method
        $paymentMethod = isset($order['payment_method']) && !empty($order['payment_method'])
            ? strtoupper(htmlspecialchars($order['payment_method']))
            : 'N/A';

        // Build items HTML
        $rowsHtml = '';
        if ($orderItems && count($orderItems) > 0) {
            foreach ($orderItems as $item) {
                $itemName = isset($item['product_name']) && !empty($item['product_name'])
                    ? htmlspecialchars($item['product_name'])
                    : (isset($item['blindbox_id']) ? 'Blindbox #' . htmlspecialchars($item['blindbox_id']) : 'Item');

                $rowsHtml .= '
                <tr>
                    <td style="padding:12px; border-bottom:1px solid #e0e0e0;">' . $itemName . '</td>
                    <td style="padding:12px; border-bottom:1px solid #e0e0e0; text-align:center;">' . (int) $item['quantity'] . '</td>
                    <td style="padding:12px; border-bottom:1px solid #e0e0e0; text-align:right;">RM ' . number_format($item['unit_price'], 2) . '</td>
                    <td style="padding:12px; border-bottom:1px solid #e0e0e0; text-align:right;">RM ' . number_format($item['subtotal'], 2) . '</td>
                </tr>
            ';
            }
        }

        // Shipping row
        $shippingRow = '';
        $shippingTotal = 0;
        if ($isShipping) {
            $shippingTotal = 5.00;
            $shippingRow = '
        <tr>
            <td colspan="3" style="padding:12px 0; text-align:right; font-weight:bold;">
                Shipping
            </td>
            <td style="padding:12px 0; text-align:right; font-weight:bold;">
                RM ' . number_format($shippingTotal, 2) . '
            </td>
        </tr>';
        }

        // Calculate subtotal (either from order or sum of items)
        $subtotal = isset($order['sub_total_amount'])
            ? floatval($order['sub_total_amount'])
            : (isset($order['total_amount']) ? $order['total_amount'] - $order['tax_amount'] - $shippingTotal : 0);

        // Mail body
        $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Confirmation</title>
        <style>
            @media only screen and (max-width: 600px) {
                .container { width: 100% !important; }
                .receipt-section { padding: 15px !important; }
                .receipt-row { padding: 8px 0 !important; }
                .receipt-items-table th,
                .receipt-items-table td { padding: 8px 5px !important; }
            }
        </style>
    </head>
    <body style="margin:0; padding:0; font-family:Arial, sans-serif; background:#f8f9fa;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; padding:30px 0;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);" class="container">
                        
                        <!-- Header -->
                        <tr>
                            <td style="background:#000000; padding:25px 30px; text-align:center;">
                                <img src="cid:logo_cid" alt="Logo" style="max-width:150px; display:block; margin:0 auto;">
                                <h1 style="color:#ffffff; margin:12px 0 0 0; font-size:16px;">Order Confirmation</h1>
                                <p style="color:#fff; margin:0; font-size:16px;">Thank you for your purchase!</p>
                            </td>
                        </tr>
                        
                        <!-- Order Details Section -->
                        <tr>
                            <td style="padding:25px 30px 15px 30px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:15px; border-bottom:2px solid;">
                                            <h2 style="color:#333; margin:0; font-size:18px;">Order Details</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:15px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding:8px 0;">
                                                        <strong style="color:#555; min-width:120px; display:inline-block;">Order ID:</strong> 
                                                        <span style="color:#333;">#' . htmlspecialchars($order['order_id']) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:8px 0;">
                                                        <strong style="color:#555; min-width:120px; display:inline-block;">Date:</strong> 
                                                        <span style="color:#333;">' . htmlspecialchars($date) . ' at ' . htmlspecialchars($time) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:8px 0;">
                                                        <strong style="color:#555; min-width:120px; display:inline-block;">Status:</strong> 
                                                        <span style="font-weight:bold;">' . strtoupper(htmlspecialchars($order['status'])) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:8px 0;">
                                                        <strong style="color:#555; min-width:120px; display:inline-block;">Payment Method:</strong> 
                                                        <span style="color:#333;">' . $paymentMethod . '</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Customer Details Section -->
                        <tr>
                            <td style="padding:15px 30px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:15px; border-bottom:2px solid;">
                                            <h2 style="color:#333; margin:0; font-size:18px;">Customer Details</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:15px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding:8px 0;">
                                                        <strong style="color:#555; min-width:120px; display:inline-block;">Customer:</strong> 
                                                        <span style="color:#333;">' . htmlspecialchars($userData->getFullname()) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:8px 0;">
                                                        <strong style="color:#555; min-width:120px; display:inline-block;">Email:</strong> 
                                                        <span style="color:#333;">' . htmlspecialchars($email) . '</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';

        // Add items table only if there are items
        if (!empty($rowsHtml)) {
            $mail->Body .= '
                        <!-- Items Section -->
                        <tr>
                            <td style="padding:15px 30px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:15px; border-bottom:2px solid;">
                                            <h2 style="color:#333; margin:0; font-size:18px;">Order Items</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:15px;">
                                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top:10px;" class="receipt-items-table">
                                                <thead>
                                                    <tr style="background:#f1f5f9;">
                                                        <th style="padding:12px; text-align:left; font-weight:bold; color:#333; border-bottom:2px solid;">Item</th>
                                                        <th style="padding:12px; text-align:center; font-weight:bold; color:#333; border-bottom:2px solid;">Qty</th>
                                                        <th style="padding:12px; text-align:right; font-weight:bold; color:#333; border-bottom:2px solid;">Price</th>
                                                        <th style="padding:12px; text-align:right; font-weight:bold; color:#333; border-bottom:2px solid;">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ' . $rowsHtml . '
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
        }

        $mail->Body .= '
                        <!-- Totals Section -->
                        <tr>
                            <td style="padding:15px 30px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:15px; border-bottom:2px solid">
                                            <h2 style="color:#333; margin:0; font-size:18px;">Order Summary</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:15px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding:12px 0; text-align:right;">
                                                        <span style="color:#555;">Subtotal:</span>
                                                    </td>
                                                    <td style="padding:12px 0; text-align:right; width:100px;">
                                                        <span style="color:#333;">RM ' . number_format($subtotal, 2) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:12px 0; text-align:right;">
                                                        <span style="color:#555;">Tax:</span>
                                                    </td>
                                                    <td style="padding:12px 0; text-align:right;">
                                                        <span style="color:#333;">RM ' . number_format($order['tax_amount'], 2) . '</span>
                                                    </td>
                                                </tr>';

        // Add shipping row if applicable
        if ($isShipping) {
            $mail->Body .= '
                                                <tr>
                                                    <td style="padding:12px 0; text-align:right;">
                                                        <span style="color:#555;">Shipping:</span>
                                                    </td>
                                                    <td style="padding:12px 0; text-align:right;">
                                                        <span style="color:#333;">RM ' . number_format($shippingTotal, 2) . '</span>
                                                    </td>
                                                </tr>';
        }

        $mail->Body .= '
                                                <tr>
                                                    <td style="padding:12px 0; text-align:right; border-top:2px solid;">
                                                        <strong style="color:#333; font-size:16px;">TOTAL:</strong>
                                                    </td>
                                                    <td style="padding:12px 0; text-align:right; border-top:2px solid #000;">
                                                        <strong style="font-size:16px;">RM ' . number_format($order['total_amount'], 2) . '</strong>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background:#f1f5f9; padding:25px 30px; text-align:center; font-size:14px;">
                                <p style="margin:0 0 10px 0;">If you have any questions about your order, please contact our customer support.</p>
                                <p style="margin:0;">Thank you for shopping with us!</p>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

        // Add QR code section if available (for pickup orders)
        if ($qrCodePath && file_exists($qrCodePath)) {
            // Insert QR code section before the footer
            $qrCodeSection = '
                        <!-- Pickup Location QR Code Section -->
                        <tr>
                            <td style="padding:15px 30px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:15px; border-bottom:2px solid;">
                                            <h2 style="color:#333; margin:0; font-size:18px;">Pickup Location</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:15px; text-align:center;">
                                            <p style="color:#555; margin:0 0 15px 0;">Scan the QR code below to get directions to the pickup location</p>
                                            <img src="cid:qr_code_cid" alt="QR Code" style="width: 250px; height:250px; display:block; margin:0 auto;"/>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
';
            // Insert before footer
            $footerPos = strpos($mail->Body, '<!-- Footer -->');
            if ($footerPos !== false) {
                $mail->Body = substr_replace($mail->Body, $qrCodeSection, $footerPos, 0);
            }
        }

        $mail->isHTML(true);
        return $mail->send();
    }
}

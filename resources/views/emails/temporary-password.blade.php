<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Temporary Credentials — Project Manager 10X Agency</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <!-- Email Container Card -->
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); border: 1px solid #e2e8f0;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background-color: #0b3d91; padding: 28px 32px; text-align: left;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px;">
                                            Project Manager 10X Agency
                                        </h1>
                                        <p style="margin: 4px 0 0; font-size: 12px; color: #93c5fd; font-weight: 500;">
                                            Payment &amp; Project Coordinator CRM
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px;">
                            <h2 style="margin: 0 0 12px; font-size: 17px; font-weight: 700; color: #0f172a;">
                                Temporary Password Issued
                            </h2>
                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                                Hello <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #475569;">
                                A temporary password has been generated for your account (<strong>{{ $user->email }}</strong>). You can now access your dashboard using the credentials below:
                            </p>

                            <!-- Credentials Box -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 8px;">
                                    Temporary Password
                                </div>
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 16px; font-weight: 700; color: #0b3d91; word-break: break-all; background-color: #ffffff; padding: 12px 14px; border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none;">
                                            {{ $temporaryPassword }}
                                        </td>
                                        <td style="width: 140px; background-color: #ffffff; border: 1px solid #cbd5e1; border-left: none; border-radius: 0 8px 8px 0; padding: 6px 10px; text-align: center; vertical-align: middle;">
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText('{{ $temporaryPassword }}'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy Password', 2500);"
                                                    style="background-color: #0b3d91; color: #ffffff; border: none; border-radius: 6px; padding: 9px 14px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-block; text-decoration: none; font-family: inherit;">
                                                Copy Password
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Login CTA -->
                            <div style="text-align: center; margin-bottom: 28px;">
                                <a href="{{ route('login') }}" style="display: inline-block; background-color: #0b3d91; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(11, 61, 145, 0.2);">
                                    Log In to CRM &rarr;
                                </a>
                            </div>

                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                If you did not request this password reset or believe this occurred in error, please alert your agency system administrator immediately.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 16px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                &copy; {{ date('Y') }} Project Manager 10X Agency. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<?php if (!defined('ABSPATH')) { exit; } ?>
<html>
    <head>
        <title><?php echo esc_attr($title) ?></title>
    </head>
    <body>
        <div id="email_container" style="background:#444">
            <div style="padding:0 0 0 20px; margin:50px auto 12px auto" id="email_header">
                        <span style="background:#585858; color:#fff; padding:12px;font-family:trebuchet ms; letter-spacing:1px;
                            -moz-border-radius-topleft:5px; -webkit-border-top-left-radius:5px;
                            border-top-left-radius:5px;moz-border-radius-topright:5px; -webkit-border-top-right-radius:5px;
                            border-top-right-radius:5px;">
                            Error Report from the website <b><?php echo esc_attr($site_url) ?></b>
                        </span>
            </div>
        </div>

        <div style="padding:0 20px 20px 20px; background:#fff; margin:0 auto; border:3px #000 solid;
                        moz-border-radius:5px; -webkit-border-radius:5px; border-radius:5px; color:#454545;line-height:1.5em; " id="email_content">

            <h1 style="padding:5px 0 0 0; font-family:georgia;font-weight:500;font-size:24px;color:#000;border-bottom:1px solid #bbb">
                <b>API Endpoint</b>: <?php echo esc_attr($endpoint) ?>
            </h1>

            <p>
                <b>Error ID</b><br/>
                <?php echo esc_attr($error_id) ?>
            </p>

            <p>
                <b>Error Code</b><br/>
                <?php echo esc_attr($error_code) ?>
            </p>

            <p>
                <b>Error Message</b><br/>
                <?php echo esc_attr($error_message) ?>
            </p>

            <p>
                <b>Request</b><br/>
                <pre><?php echo esc_attr($request) ?></pre>
            </p>

            <p>
                WordPress version: <?php echo esc_attr($wp_version) ?><br/>
                WooCommerce version: <?php echo esc_attr($wc_version) ?><br/>
                Plugin version: <?php echo esc_attr($plugin_version) ?>
            </p>

            <p>
                This is a generated e-mail, sent by the DHL plugin.
            </p>

            <p style="">
                Warm regards,<br>
                DHL for WooCommerce plugin team
            </p>

            <div style="text-align:center; border-top:1px solid #eee;padding:5px 0 0 0;" id="email_footer">
                <small style="font-size:11px; color:#999; line-height:14px;">
                    You have received this email because you are part of the DHL for WooCommerce plugin debug mailing list.
                </small>
            </div>

        </div>
    </body>
</html>

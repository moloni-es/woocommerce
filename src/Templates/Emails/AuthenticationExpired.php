<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<!doctype html>
<html lang="pt">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <title><?= __('The Moloni authentication expired', 'moloni_es') ?></title>
    <meta name="description" content="<?= __('Authentication Expired Email Template.', 'moloni_es') ?>">
    <style type="text/css">
        a:hover {
            text-decoration: underline !important;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #303A4D;" leftmargin="0">
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#303A4D"
       style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
    <tr>
        <td>
            <table style="background-color: #303A4D; max-width:670px;  margin:0 auto;" width="100%" border="0"
                   align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="height:80px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="text-align:center;">
                        <a href="<?= $url ?? '' ?>" title="logo" target="_blank">
                            <img width="200px" src="<?= $image ?? '' ?>" title="logo"
                                 alt="logo">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="height:20px;">&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                               style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                            <tr>
                                <td style="height:40px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding:0 35px;">
                                    <h1 style="color:#1e1e2d; font-weight:500; margin:0;font-size:28px;font-family:'Rubik',sans-serif;">
                                        <?= __('The Moloni authentication expired', 'moloni_es') ?>
                                    </h1>
                                    <span style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;"></span>
                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        <?= __("You're getting this email because there was an error getting new Moloni authentication tokens in your Wordpress plugin.", 'moloni_es') ?>
                                    </p>

                                    <br>

                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        <?= __('Please access the Moloni Spain plugin from your WooCommerce store admin panel and confirm the authentication status of your account.', 'moloni_es') ?>
                                    </p>

                                    <br>

                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        <?=
                                        sprintf(
                                            __('If the authentication has expired, please proceed with the authentication process again, which you can consult <a href="%s">here</a>.', 'moloni_es'),
                                            'https://www.moloni.es/faqs/como-conectar-tu-tienda-prestashop-con-moloni'
                                        );
                                        ?>
                                    </p>

                                    <br>

                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        <?= __('If you have any questions or concerns, please contact our customer support at 910 780 054 or via email soporte@moloni.es.', 'moloni_es') ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="height:40px;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
            </table>
        </td>
    </tr>
    <tr>
        <td style="height:20px;">&nbsp;</td>
    </tr>
    <tr>
        <td style="text-align:center;">
            <p style="font-size:14px; color:#fff; line-height:18px; margin:0 0 0;">
                &copy; <strong><?= $year ?? '' ?> Moloni</strong>
            </p>
        </td>
    </tr>
    <tr>
        <td style="height:80px;">&nbsp;</td>
    </tr>
</table>
</body>

</html>

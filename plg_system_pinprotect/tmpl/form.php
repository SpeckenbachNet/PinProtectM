<?php

/**
 * PIN page. Rendered by PinProtect::renderForm() as a complete HTML document.
 *
 * @var string $error          Error message (plain text) or ''
 * @var string $action         Form target (the requested URL)
 * @var string $inputName      Name of the PIN input
 * @var string $langTag        Language tag, e.g. de-DE
 * @var bool   $rtl            Right-to-left language
 * @var string $siteName       Site name for the page title
 * @var string $header         Header content (HTML from the editor)
 * @var string $footer         Footer content (HTML from the editor)
 * @var string $stylesheet     Optional stylesheet URL
 * @var string $customCss      Custom CSS
 * @var bool   $privacyNotice  Show the privacy notice
 * @var string $privacyUrl     Link to the privacy policy or ''
 * @var string $imprintUrl     Link to the imprint or ''
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$title = Text::_('PLG_SYSTEM_PINPROTECT_PAGE_TITLE') . ($siteName !== '' ? ' · ' . $siteName : '');
?>
<!DOCTYPE html>
<html lang="<?php echo $e($langTag); ?>" dir="<?php echo $rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $e($title); ?></title>
    <?php // Order matters, later wins: built-in styles, then the stylesheet, then the custom CSS ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .pinprotect {
            box-sizing: border-box;
            padding: 60px 16px 140px;
            text-align: center;
        }
        .pinprotect-box {
            display: inline-block;
            box-sizing: border-box;
            width: 100%;
            max-width: 400px;
            padding: 30px 20px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .pinprotect-box h1 {
            margin: 0 0 20px;
            font-size: 1.4rem;
        }
        .pinprotect-error {
            margin: 0 0 15px;
            color: #b02a37;
        }
        .pinprotect-input {
            box-sizing: border-box;
            width: 100%;
            max-width: 250px;
            margin-bottom: 15px;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #adb5bd;
            border-radius: 0.375rem;
        }
        .pinprotect-button {
            padding: 10px 25px;
            font-size: 1rem;
            color: #fff;
            background-color: #0d6efd;
            border: 1px solid #0d6efd;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .pinprotect-button:hover {
            background-color: #0b5ed7;
        }
        .pinprotect-notice {
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 999999;
            box-sizing: border-box;
            width: 100%;
            padding: 20px 16px;
            color: #fff;
            background: #343a40;
            text-align: center;
        }
        .pinprotect-notice-inner {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: center;
            max-width: 1000px;
            margin: 0 auto;
        }
        .pinprotect-notice a {
            color: inherit;
            text-decoration: underline;
        }
        .pinprotect-notice button {
            padding: 8px 20px;
            color: #343a40;
            background: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .pinprotect-links {
            margin: 15px 0 0;
            font-size: 0.9rem;
        }
        .pinprotect-links a {
            color: inherit;
        }
        @media (max-width: 480px) {
            .pinprotect { padding-top: 30px; }
            .pinprotect-box { padding: 20px 15px; }
            .pinprotect-box h1 { font-size: 1.2rem; }
        }
    </style>
    <?php if ($stylesheet !== '') : ?>
    <link rel="stylesheet" href="<?php echo $e($stylesheet); ?>">
    <?php endif; ?>
    <?php if (trim($customCss) !== '') : ?>
    <style>
        /* Custom CSS from the plugin settings */
        <?php echo str_ireplace('</style', '', $customCss); ?>
    </style>
    <?php endif; ?>
</head>
<body>
<div class="pinprotect">
    <?php if (trim($header) !== '') : ?>
    <div class="pinprotect-header"><?php echo $header; ?></div>
    <?php endif; ?>

    <div class="pinprotect-box">
        <h1><?php echo Text::_('PLG_SYSTEM_PINPROTECT_FORM_HEADING'); ?></h1>

        <?php if ($error !== '') : ?>
        <p class="pinprotect-error" role="alert"><?php echo $e($error); ?></p>
        <?php endif; ?>

        <form method="post" action="<?php echo $e($action); ?>">
            <input class="pinprotect-input" type="password" id="pinprotect-pin" name="<?php echo $e($inputName); ?>"
                placeholder="<?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_FORM_PLACEHOLDER')); ?>"
                aria-label="<?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_FORM_PLACEHOLDER')); ?>"
                autocomplete="off" required autofocus>
            <br>
            <button class="pinprotect-button" type="submit"><?php echo Text::_('PLG_SYSTEM_PINPROTECT_FORM_SUBMIT'); ?></button>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div>

    <?php if ($imprintUrl !== '' || $privacyUrl !== '') : ?>
    <p class="pinprotect-links">
        <?php if ($imprintUrl !== '') : ?>
        <a href="<?php echo $e($imprintUrl); ?>"><?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_LINK_IMPRINT')); ?></a>
        <?php endif; ?>
        <?php if ($imprintUrl !== '' && $privacyUrl !== '') : ?>
        <span aria-hidden="true">·</span>
        <?php endif; ?>
        <?php if ($privacyUrl !== '') : ?>
        <a href="<?php echo $e($privacyUrl); ?>"><?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_LINK_PRIVACY')); ?></a>
        <?php endif; ?>
    </p>
    <?php endif; ?>

    <?php if (trim($footer) !== '') : ?>
    <div class="pinprotect-footer"><?php echo $footer; ?></div>
    <?php endif; ?>
</div>

<?php if ($privacyNotice) : ?>
<div class="pinprotect-notice" id="pinprotect-notice" hidden>
    <div class="pinprotect-notice-inner">
        <span>
            <?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_NOTICE_TEXT')); ?>
            <?php if ($privacyUrl !== '') : ?>
            <a href="<?php echo $e($privacyUrl); ?>"><?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_NOTICE_LINK')); ?></a>
            <?php endif; ?>
        </span>
        <button type="button" id="pinprotect-notice-ok"><?php echo $e(Text::_('PLG_SYSTEM_PINPROTECT_NOTICE_OK')); ?></button>
    </div>
</div>
<script>
(function () {
    var key = 'pinprotect_notice_ok';
    var notice = document.getElementById('pinprotect-notice');
    var confirmed = false;

    try {
        confirmed = window.localStorage.getItem(key) === '1';
    } catch (e) {
        // Storage blocked: show the notice every time
    }

    if (confirmed) {
        return;
    }

    notice.hidden = false;
    document.getElementById('pinprotect-notice-ok').addEventListener('click', function () {
        try {
            window.localStorage.setItem(key, '1');
        } catch (e) {
            // Storage blocked: hide for this page view only
        }

        notice.hidden = true;
    });
})();
</script>
<?php endif; ?>
</body>
</html>

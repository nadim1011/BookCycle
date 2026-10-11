<?php

declare(strict_types=1);

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    if (!isset($_POST['_csrf'])) {
        throw new RuntimeException('সুরক্ষা টোকেন অনুপস্থিত।');
    }

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['_csrf'])) {
        throw new RuntimeException('অবৈধ নিরাপত্তা টোকেন।');
    }
}

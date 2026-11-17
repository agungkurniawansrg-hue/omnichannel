<?php
// Layout atas: <head>, sidebar, navbar. Set $pageTitle, $activeMenu, $breadcrumb, $bodyClass, $extraCss sebelum include.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/data.php';
$pageTitle  = isset($pageTitle) ? $pageTitle : 'Dashboard';
$activeMenu = isset($activeMenu) ? $activeMenu : '';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : [];
$bodyClass  = isset($bodyClass) ? $bodyClass : '';
if ($activeMenu !== '') require_access($activeMenu); else require_login();
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<div class="app-shell">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="app-main">
<?php include __DIR__ . '/navbar.php'; ?>
<main class="app-content">

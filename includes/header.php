<?php
require_once __DIR__ . '/../core/init.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title).' | BIT-InternshipHub' : 'BIT-InternshipHub' ?></title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/mdb.min.css" rel="stylesheet">
    <link href="css/style.css?v=3" rel="stylesheet">
    <link href="css/components.css?v=3" rel="stylesheet">
    <?php if (in_array($current_page, array('career-guide.php', 'career-skill.php'), true)): ?><link href="css/career-guide.css?v=1" rel="stylesheet"><?php endif; ?>
    <?php if ($current_page === 'career-skill.php'): ?><link href="css/career-skill.css?v=1" rel="stylesheet"><?php endif; ?>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light default-color site-navbar" aria-label="Main navigation">
            <a class="navbar-brand" href="index.php"><span class="brand-mark" aria-hidden="true">B</span><span>BIT-InternshipHub</span></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"><span></span></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php" <?= $current_page === 'index.php' ? 'aria-current="page"' : '' ?>>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'internship.php' ? 'active' : '' ?>" href="internship.php" <?= $current_page === 'internship.php' ? 'aria-current="page"' : '' ?>>Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($current_page, array('career-guide.php', 'career-skill.php'), true) ? 'active' : '' ?>" href="career-guide.php" <?= in_array($current_page, array('career-guide.php', 'career-skill.php'), true) ? 'aria-current="page"' : '' ?>>Career guide</a>
                    </li>
                    <?php if(is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'myaccount.php' ? 'active' : '' ?>" href="myaccount.php" <?= $current_page === 'myaccount.php' ? 'aria-current="page"' : '' ?>>My Account</a>
                        </li>
                        <?php if(has_permission('superadmin')): ?><li class="nav-item"><a class="nav-link" href="SuperAdmin/index.php">Superadmin</a></li><?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link nav-action" href="logout.php">Sign out</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                        <a class="nav-link" href="register.php">Create account</a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link nav-action" href="login.php">Sign in</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main class="container site-main" id="main-content" tabindex="-1">
        <?php if($message = flash('success_flash')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= sanitize($message) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        <?php if($message = flash('error_flash')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= sanitize($message) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learning Management System - Browse courses, learn new skills">
    <title>
        <?php echo isset($pageTitle) ? e($pageTitle) . ' | LMS' : 'LMS - Learning Management System'; ?>
    </title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
</head>

<body>
    <div class="page-wrapper">
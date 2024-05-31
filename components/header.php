<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$firstDir = explode('/', trim($currentDir, '/'));

$tzList = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

if (in_array('Asia/Manila', $tzList)) {
    date_default_timezone_set("Asia/Manila");
} else {
    date_default_timezone_set("Asia/Singapore");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVSU Grading System</title>

    <link rel="shortcut icon" href="<?= str_repeat("../", count($firstDir) - 1) ?>assets/images/favicon.ico"
        type="image/x-icon">

    <!-- Box Icons -->
    <link href='<?= str_repeat("../", count($firstDir) - 1) ?>assets/css/boxicons/boxicons@2.1.4.min.css'
        rel='stylesheet'>

    <!-- Fontawesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Fonts -->
    <link href="<?= str_repeat("../", count($firstDir) - 1) ?>assets/css/poppins/poppins.css" rel="stylesheet">

    <!-- Multi-select -->
    <link href="<?= str_repeat("../", count($firstDir) - 1) ?>assets/css/tom-select/tom-select@2.3.1.css"
        rel="stylesheet">
    <script src="<?= str_repeat("../", count($firstDir) - 1) ?>assets/js/tom-select/tom-select@2.3.1.min.js"></script>

    <!-- Alpine Core -->
    <script defer src="<?= str_repeat("../", count($firstDir) - 1) ?>assets/js/alpinejs/plugins/mask@3.13.5.min.js">
    </script>
    <!-- <script defer src="<?= "" // str_repeat("../", count($firstDir) - 1) ?>assets/js/alpinejs/plugins/csp@3.13.5.min.js"></script> -->
    <script src="<?= str_repeat("../", count($firstDir) - 1) ?>assets/js/alpinejs/alpinejs@3.13.5.min.js" defer>
    </script>

    <!-- Daisy UI -->
    <link href="<?= str_repeat("../", count($firstDir) - 1) ?>assets/css/daisyui/daisyui@4.5.0.min.css" rel="stylesheet"
        type="text/css" />

    <!-- Tailwind CSS -->
    <script src="<?= str_repeat("../", count($firstDir) - 1) ?>assets/js/tailwindcss/tailwindcss@3.4.3.min.js"></script>

    <!-- Preloader CSS -->
    <link rel="stylesheet" href="<?= str_repeat("../", count($firstDir) - 1) ?>assets/css/preloader/preloader.css">

    <!-- Jquery -->
    <script src="<?= str_repeat("../", count($firstDir) - 1) ?>assets/js/jquery/jquery-3.4.1.min.js"></script>

    <!-- Preloader JS -->
    <script src="<?= str_repeat("../", count($firstDir) - 1) ?>assets/js/preloader/preloader.js"></script>

    <!-- Global style -->
    <style>
    * {
        -ms-overflow-style: none;
        /* Internet Explorer 10+ */
        scrollbar-width: none;
        /* Firefox */
    }

    *::-webkit-scrollbar {
        display: none;
        /* Safari and Chrome */
    }

    body {
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
    }

    @media (min-width: 768px) {
        :root {
            font-size: 16px;
            min-height: 0vw;
        }
    }

    @media (min-width: 1920px) {
        :root {
            font-size: 16px;
        }
    }
    </style>
</head>

<body>
    <!-- Preloader -->
    <?php include_once (str_repeat('../', count($firstDir) - 1) . 'components/preloader.php') ?>
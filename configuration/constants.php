<?php
// constants
$constants = [
    'DATABASE' => [
        'DB_HOST' => 'localhost:3307',
        'DB_USER' => 'root',
        'DB_PASS' => '',
        'DB_NAME' => 'grading-sys'
    ],
    'SMTP' => [
        'SMTP_HOST' => 'smtp.hostinger.com',
        'SMTP_USER' => 'no-reply@hopiesoberanya.dev',
        'SMTP_PASS' => 'Admin@21',
        'SMTP_SECURE' => 'ssl',
        'SMTP_PORT' => 465
    ],
    'MAIL' => [
        'MAIL_FROM' => 'no-reply@hopiesoberanya.dev',
        'MAIL_NAME' => 'CVSU Grading System'
    ],
    'APP' => [
        'APP_URL' => 'https://cvsu-grading-sys.infinityfreeapp.com',
        'USER_DEFAULT_PASSWORD' => 'cvsu@123' // Default password
    ]
];

// define as constants
foreach ($constants as $constant => $values) {
    foreach ($values as $key => $value) {
        define($key, $value);
    }
}

?>
<?php
// Configuración de SIGSM Nordlyn.
// En Debian nativo se utilizan los valores locales; Docker puede reemplazarlos mediante variables de entorno.
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'sigsm_nordlyn',
    'db_user' => getenv('DB_USER') ?: 'sigsm_user',
    'db_pass' => getenv('DB_PASS') ?: 'CambiarEstaClave_DB_2026',
    // Claves oficiales de prueba de Google reCAPTCHA v2 (solo para desarrollo).
    'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
    'recaptcha_secret_key' => getenv('RECAPTCHA_SECRET_KEY') ?: '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
    'base_url' => getenv('BASE_URL') ?: 'http://localhost',
];

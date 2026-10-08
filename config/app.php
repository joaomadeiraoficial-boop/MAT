<?php
return [
    'app_name' => 'MAT — Laboratório Têmporis',
    'version' => '6.4.0-alpha',
    'timezone' => 'America/Sao_Paulo',
    'base_url' => '',
    'upload_dir' => __DIR__ . '/../uploads/imports',
    'max_upload_bytes' => 8 * 1024 * 1024,
    'duplicate_threshold' => 0.82,
    'duplicate_year_tolerance' => 5,
    'temporis' => [
        'alpha' => 0.65,
        'base_year' => 2026,
        'base_value' => 100.0,
    ],
];

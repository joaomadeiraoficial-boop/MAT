<?php
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'mat_observatorio',
    // Curadoria de trabalho do Refinado, sem mudança de banco.
    'curadoria_db_name' => 'mat_temporis_curadoria',
    // Base historica isolada (importacao opcional do MAT Final).
    'temporis_final_db_name' => 'mat_final_curadoria',
    'db_user' => 'root',
    'db_pass' => '',
    'reference_year' => 2025,
    'app_name' => 'MAT — Versão Refinada 0.1 | Observatório do Tempo',
];

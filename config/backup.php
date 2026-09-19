<?php

declare(strict_types=1);

return [
    // Nombre de sauvegardes à conserver (rotation glissante).
    'keep' => (int) env('BACKUP_KEEP', 7),

    // Binaire mysqldump : chemin complet si absent du PATH (ex : C:\wamp64\bin\mysql\mysql8.4\bin\mysqldump.exe).
    // La commande auto-détecte aussi les installs WAMP dans C:\wamp64\bin\mysql\*\bin\.
    'mysqldump_bin' => env('MYSQLDUMP_BIN', 'mysqldump'),
];
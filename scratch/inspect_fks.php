<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fks = \Illuminate\Support\Facades\DB::select("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE REFERENCED_TABLE_SCHEMA = 'central-ticket'
      AND REFERENCED_TABLE_NAME = 'odps'
");

echo "Foreign keys referencing `odps` table: " . count($fks) . "\n";
foreach ($fks as $fk) {
    echo "  {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME} ({$fk->CONSTRAINT_NAME})\n";
}

$indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM odps");
echo "\nCurrent indexes on `odps`:\n";
foreach ($indexes as $idx) {
    echo "  Key: {$idx->Key_name} | Column: {$idx->Column_name} | Non_unique: {$idx->Non_unique}\n";
}

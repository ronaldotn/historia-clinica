<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Renombrar tabla si existe con nombre incorrecto
        if (Schema::hasTable('practitions') && !Schema::hasTable('practitioners')) {
            Schema::rename('practitions', 'practitioners');
        }
    }

    public function down(): void
    {
        // Revertir solo si originalmente existía 'practitions' y no existe ya
        if (Schema::hasTable('practitioners') && !Schema::hasTable('practitions')) {
            Schema::rename('practitioners', 'practitions');
        }
    }
};

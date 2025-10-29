<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('practitioners', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 50)->unique()->comment('matrícula, licencia');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('specialty', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->unique();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practitioners');
    }
};

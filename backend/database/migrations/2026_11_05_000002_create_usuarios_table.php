<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->string('nombre', 100);

            // Acceso del personal: nombre de usuario + PIN (guardado con hash)
            $table->string('usuario', 50)->nullable()->unique();
            $table->string('pin')->nullable();

            // Acceso del administrador con Google (OAuth 2.0)
            $table->string('email')->nullable()->unique();
            $table->string('google_id')->nullable()->unique();

            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

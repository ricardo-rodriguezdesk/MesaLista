<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->index()->constrained('zonas')->restrictOnDelete();
            $table->string('nombre', 20)->unique();
            $table->unsignedSmallInteger('capacidad')->default(4);
            $table->enum('forma', ['cuadrada', 'redonda', 'barra'])->default('cuadrada');

            // Posición, tamaño y giro en el croquis
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->unsignedInteger('ancho')->default(80);
            $table->unsignedInteger('alto')->default(80);
            $table->smallInteger('rotacion')->default(0);

            $table->enum('estado', ['libre', 'ocupada', 'cuenta_pedida'])->default('libre');
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};

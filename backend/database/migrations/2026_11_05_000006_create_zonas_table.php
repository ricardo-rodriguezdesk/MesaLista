<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->enum('tipo', ['salon', 'terraza', 'barra'])->default('salon');

            // Posición y tamaño en el croquis (en píxeles del lienzo)
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->unsignedInteger('ancho')->default(400);
            $table->unsignedInteger('alto')->default(300);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};

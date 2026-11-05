<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->index()->constrained('categorias')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();

            // Precio final al público (con IVA incluido) y tasa de IVA
            $table->decimal('precio', 10, 2);
            $table->decimal('tasa_iva', 5, 4)->default(0.16);

            // Claves del catálogo del SAT requeridas para el CFDI 4.0
            $table->string('clave_prod_serv', 8)->default('90101501');
            $table->string('clave_unidad', 3)->default('E48');

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['categoria_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

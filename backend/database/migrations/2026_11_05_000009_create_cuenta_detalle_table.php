<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuenta_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->index()->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedSmallInteger('cantidad')->default(1);

            // Copia del precio y la tasa al momento de pedir
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('tasa_iva', 5, 4);

            $table->string('notas')->nullable();
            $table->unsignedSmallInteger('comensal')->nullable();
            $table->enum('estado', ['pendiente', 'preparando', 'listo', 'entregado', 'cancelado'])->default('pendiente');

            $table->timestamp('enviado_en')->useCurrent();
            $table->timestamp('listo_en')->nullable();

            // Pantalla de cocina: pedidos por estado en orden de llegada
            $table->index(['estado', 'enviado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuenta_detalle');
    }
};

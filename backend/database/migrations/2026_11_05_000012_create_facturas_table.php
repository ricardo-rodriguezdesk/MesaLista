<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('tickets')->restrictOnDelete();

            // Datos devueltos por el PAC (Facturapi)
            $table->string('facturapi_id')->nullable();
            $table->string('uuid', 36)->nullable()->unique();

            // Datos fiscales del cliente (CFDI 4.0)
            $table->string('rfc', 13);
            $table->string('razon_social');
            $table->string('regimen_fiscal', 3);
            $table->string('codigo_postal', 5);
            $table->string('uso_cfdi', 4);
            $table->string('email');

            // Rutas de los archivos en Supabase Storage
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->decimal('total', 10, 2);
            $table->enum('estado', ['pendiente', 'timbrada', 'cancelada', 'error'])->default('pendiente');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};

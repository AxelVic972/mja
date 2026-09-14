<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adhesions', function (Blueprint $table) {
            $table->string('statut', 30)->default('en_attente_paiement')->change();
        });
    }

    public function down(): void
    {
        Schema::table('adhesions', function (Blueprint $table) {
            $table->string('statut', 30)->default('nouvelle')->change();
        });
    }
};

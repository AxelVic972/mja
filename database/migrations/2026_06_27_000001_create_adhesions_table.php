<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adhesions', function (Blueprint $table) {
            $table->id();
            $table->string('premiere_adhesion', 50);
            $table->string('civilite', 20);
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('date_naissance', 20);
            $table->string('profession', 150);
            $table->string('telephone', 30);
            $table->string('email', 150);
            $table->text('adresse_postale');
            $table->string('taille_tshirt', 10);
            $table->string('permis', 5);
            $table->text('problemes_sante')->nullable();
            $table->string('urgence_contact', 300);
            $table->boolean('droit_image')->default(false);
            $table->string('statut', 30)->default('en_attente_paiement');
            $table->boolean('lu')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adhesions');
    }
};

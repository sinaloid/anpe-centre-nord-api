<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offres', function (Blueprint $table) {
            $table->id();
            $table->string("label");
            $table->string("type");

            $table->string("entreprise")->nullable();
            $table->string("type_contrat")->nullable();
            $table->string("type_contrat_id")->nullable();

            $table->string("niveau_etude")->nullable();
            $table->string("niveau_etude_id")->nullable();

            $table->string("niveau_experience")->nullable();
            $table->string("niveau_experience_id")->nullable();

            $table->string("salaire")->nullable();
            $table->string("nombre_de_poste")->nullable();

            $table->string("date_debut")->nullable();
            $table->string("date_limite")->nullable();

            $table->string("region")->nullable();
            $table->string("region_id")->nullable();

            $table->string("ville")->nullable();
            $table->string("ville_id")->nullable();
            $table->string("longitude")->nullable();
            $table->string("latitude")->nullable();
            $table->string("pieces_jointes")->nullable();
            $table->string("etat")->nullable();

            $table->longtext("description")->nullable();
            $table->string("slug");
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('offres');
    }
};

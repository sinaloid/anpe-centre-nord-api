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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('date_de_naissance');
            $table->string('genre');
            $table->string('telephone');
            $table->string('profile');
            $table->string('matricule');
            $table->string('slug');
            $table->string('email')->unique();
            $table->boolean('two_factor_is_active')->default(false);
            $table->string('two_factor_code')->nullable();
            $table->string('two_factor_expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('telephone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};

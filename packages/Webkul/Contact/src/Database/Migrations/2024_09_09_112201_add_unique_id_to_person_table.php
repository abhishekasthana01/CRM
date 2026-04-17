<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('unique_id')->nullable()->unique();
        });

        $tableName = DB::getTablePrefix().'persons';

        DB::table('persons')->get()->each(function ($person) {
            $emails = json_decode($person->emails, true);
            $contactNumbers = json_decode($person->contact_numbers, true);

            $email = $emails[0]['value'] ?? '';
            $contactNumber = $contactNumbers[0]['value'] ?? '';

            DB::table('persons')
                ->where('id', $person->id)
                ->update([
                    'unique_id' => "{$person->user_id}|{$person->organization_id}|{$email}|{$contactNumber}",
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            if (DB::getDriverName() !== "sqlite") $table->dropColumn('unique_id');
        });
    }
};

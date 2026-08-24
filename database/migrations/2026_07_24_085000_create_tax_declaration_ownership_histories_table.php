<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_declaration_ownership_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_declaration_id')->constrained('tax_declarations')->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('property_owners')->nullOnDelete();

            // Snapshot at transfer time (display stays accurate if owner profile changes)
            $table->string('owner_name');
            $table->string('owner_tin', 20)->nullable();
            $table->text('owner_address')->nullable();
            $table->string('owner_telephone', 20)->nullable();

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->date('transfer_date');
            $table->string('transfer_reason')->nullable();
            $table->text('remarks')->nullable();

            $table->string('previous_td_number')->nullable();
            $table->decimal('previous_av', 15, 2)->nullable();

            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tax_declaration_id', 'transfer_date'], 'td_ownership_hist_td_date_idx');
            $table->index('owner_id', 'td_ownership_hist_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_declaration_ownership_histories');
    }
};

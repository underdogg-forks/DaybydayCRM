<?php

use App\Models\InvoiceLine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterInvoicesTableAddSource extends Migration
{
    protected $invoiceLines;

    public function up()
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        Schema::table('invoices', static function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('integration_type');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_id']);
            $table->integer('offer_id')->unsigned()->nullable()->after('client_id');
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('set null');
        });

        if (! $isSqlite) {
            Schema::table('leads', static function (Blueprint $table) {
                $table->dropForeign('leads_invoice_id_foreign');
                $table->dropColumn('invoice_id');
            });
            Schema::table('tasks', static function (Blueprint $table) {
                $table->dropForeign('tasks_invoice_id_foreign');
                $table->dropColumn('invoice_id');
            });
        }
        // On SQLite we leave leads.invoice_id and tasks.invoice_id in place — the extra
        // nullable column is harmless for testing purposes.

        $this->invoiceLines = InvoiceLine::all();

        if (! $isSqlite) {
            Schema::table('invoice_lines', function (Blueprint $table) {
                $table->integer('offer_id')->unsigned()->nullable()->after('price');
                $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
                $table->dropForeign('invoice_lines_invoice_id_foreign');
                $table->dropColumn('invoice_id');
            });
        } else {
            Schema::table('invoice_lines', static function (Blueprint $table) {
                $table->integer('offer_id')->unsigned()->nullable();
            });
        }

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->integer('invoice_id')->unsigned()->nullable()->after('price');
            if (! (DB::getDriverName() === 'sqlite')) {
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            }
            foreach ($this->invoiceLines as $invoiceLine) {
                DB::table('invoice_lines')->where('id', $invoiceLine->id)->update(['invoice_id' => $invoiceLine->invoice_id]);
            }
        });
    }

    public function down() {}
}

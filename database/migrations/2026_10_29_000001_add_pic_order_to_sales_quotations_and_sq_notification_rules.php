<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PIC Order di Sales Quotation (dibawa ke SO saat Terbitkan SO) dan ikut jadi penerima notifikasi event SQ.
 *
 * - Kolom `sales_quotations.pic_order` (users.id, nullable: SQ lama tidak punya nilainya).
 * - Rule notifikasi event sq.* bertipe 'roles' yang sudah ada: 'pic_order' DITAMBAHKAN ke daftar peran
 *   (peran lain & rule "User tertentu" buatan admin tidak diubah; tidak ditambahkan dua kali).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales_quotations', 'pic_order')) {
            Schema::table('sales_quotations', function (Blueprint $table) {
                $table->unsignedBigInteger('pic_order')->nullable()->after('pic_input');
            });
        }

        $eventIds = DB::table('notification_events')->where('key', 'like', 'sq.%')->pluck('id');
        foreach ($eventIds as $eventId) {
            DB::table('notification_events')->where('id', $eventId)->update([
                'available_roles' => json_encode(['actor', 'pengaju', 'pic_input', 'pic_order', 'marketing_internal', 'marketing_eksternal']),
                'updated_at' => now(),
            ]);

            $rules = DB::table('notification_rules')
                ->where('notification_event_id', $eventId)
                ->where('recipient_type', 'roles')
                ->get(['id', 'recipient_roles']);

            foreach ($rules as $rule) {
                $roles = json_decode($rule->recipient_roles ?? '[]', true) ?: [];
                if (in_array('pic_order', $roles, true)) continue;
                $roles[] = 'pic_order';
                DB::table('notification_rules')->where('id', $rule->id)->update([
                    'recipient_roles' => json_encode(array_values($roles)),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $eventIds = DB::table('notification_events')->where('key', 'like', 'sq.%')->pluck('id');
        foreach ($eventIds as $eventId) {
            $rules = DB::table('notification_rules')
                ->where('notification_event_id', $eventId)
                ->where('recipient_type', 'roles')
                ->get(['id', 'recipient_roles']);
            foreach ($rules as $rule) {
                $roles = array_values(array_diff(json_decode($rule->recipient_roles ?? '[]', true) ?: [], ['pic_order']));
                DB::table('notification_rules')->where('id', $rule->id)->update(['recipient_roles' => json_encode($roles)]);
            }
        }

        if (Schema::hasColumn('sales_quotations', 'pic_order')) {
            Schema::table('sales_quotations', function (Blueprint $table) {
                $table->dropColumn('pic_order');
            });
        }
    }
};

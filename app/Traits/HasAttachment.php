<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait HasAttachment
{
    abstract protected function attachmentTable(): string;
    abstract protected function attachmentPrimaryKey(): string;

    public function deleteAttachment(Request $request)
    {
        $id    = $request->id;
        $file  = $request->file;
        $table = $this->attachmentTable();
        $pk    = $this->attachmentPrimaryKey();

        $data = DB::table($table)->where($pk, $id)->first();

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $attachments = json_decode($data->attachment, true) ?? [];
        $attachments = array_values(array_filter($attachments, fn($item) => $item !== $file));

        Storage::disk('public')->delete($file);

        DB::table($table)->where($pk, $id)->update([
            'attachment' => json_encode($attachments),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}

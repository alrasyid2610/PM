<?php

function uploadAttachment($fieldAttachments, $table)
{
    $files = [];

    if (!$fieldAttachments) {
        return [
            'status' => false,
            'message' => 'Tidak ada file',
            'files' => []
        ];
    }

    foreach ($fieldAttachments as $file) {

        $originalName = $file->getClientOriginalName();

        // bersihkan spasi
        $originalName = str_replace(' ', '_', $originalName);

        // prefix + timestamp supaya tidak overwrite
        $fileName = $table . '_' . time() . '_' . $originalName;

        $path = $file->storeAs(
            'attachments/' . $table,
            $fileName,
            'public'
        );

        $files[] = $path;
    }

    return [
        'status' => true,
        'message' => 'Berhasil store file',
        'files' => $files
    ];
}

<?php

namespace App\Http\Controllers;

class BoqOtherController extends BoqTambahanController
{
    protected function jenis(): string
    {
        return 'lainnya';
    }
}

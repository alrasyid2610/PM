<?php

namespace App\Http\Controllers;

class FwoBoqOtherController extends FwoBoqTambahanController
{
    protected function jenis(): string
    {
        return 'lainnya';
    }
}

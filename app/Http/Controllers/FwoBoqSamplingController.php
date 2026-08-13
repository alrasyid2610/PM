<?php

namespace App\Http\Controllers;

class FwoBoqSamplingController extends FwoBoqTambahanController
{
    protected function jenis(): string
    {
        return 'sampling';
    }
}

<?php

namespace App\Http\Controllers;

class BoqSamplingController extends BoqTambahanController
{
    protected function jenis(): string
    {
        return 'sampling';
    }
}

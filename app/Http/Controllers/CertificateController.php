<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificateController extends Controller
{
    public function show(Certificate $certificate)
    {
        $certificate->load(['customer', 'product']);
        return view('certificates.verify', compact('certificate'));
    }
    public function print(Certificate $certificate)
    {
        $certificate->load(['customer', 'product']);
        return view('certificates.print', compact('certificate'));
    }
}

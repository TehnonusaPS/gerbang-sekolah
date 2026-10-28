<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aset;
use App\Models\School;

$school = School::first();

if ($school) {
    Aset::firstOrCreate(
        ['code' => 'INV-ELK-001'],
        [
            'school_id' => $school->id,
            'name'      => 'Proyektor Epson EB-X400',
            'category'  => 'Elektronik',
            'brand'     => 'Epson',
            'building'  => 'Gedung A',
            'floor'     => '1',
            'room'      => 'Ruang Kelas 2-D',
            'condition' => 'Baik',
            'date'      => '2023-01-10',
            'value'     => 4500000,
        ]
    );

    Aset::firstOrCreate(
        ['code' => 'INV-FURN-001'],
        [
            'school_id' => $school->id,
            'name'      => 'Meja Guru Kayu Jati',
            'category'  => 'Furniture',
            'brand'     => 'Olympic',
            'building'  => 'Gedung Utama',
            'floor'     => '1',
            'room'      => 'Ruang Guru',
            'condition' => 'Rusak Ringan',
            'date'      => '2020-05-15',
            'value'     => 1200000,
        ]
    );

    Aset::firstOrCreate(
        ['code' => 'INV-LAB-001'],
        [
            'school_id' => $school->id,
            'name'      => 'Mikroskop Binokuler',
            'category'  => 'Peralatan Laboratorium',
            'brand'     => 'Olympus',
            'building'  => 'Gedung Laboratorium',
            'floor'     => '2',
            'room'      => 'Lab Biologi',
            'condition' => 'Baik',
            'date'      => '2024-02-20',
            'value'     => 8500000,
        ]
    );
    echo "Berhasil memasukkan 3 aset ke dalam database!\n";
} else {
    echo "Sekolah tidak ditemukan.\n";
}

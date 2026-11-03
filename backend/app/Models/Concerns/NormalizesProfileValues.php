<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait NormalizesProfileValues
{
    public const AGAMA_MAP = [
        'A01' => 'Islam',
        'A02' => 'Kristen',
        'A03' => 'Katolik',
        'A04' => 'Buddha',
        'A05' => 'Hindu',
        'A06' => 'Konghucu',
    ];

    public const MARITAL_MAP = [
        'SP01' => 'Belum Menikah',
        'SP02' => 'Menikah',
        'SP03' => 'Janda',
        'SP04' => 'Duda',
    ];

    public const EDUCATION_MAP = [
        'P01' => 'Sekolah Dasar (SD)',
        'SD'  => 'Sekolah Dasar (SD)',
        'P02' => 'Sekolah Menengah Pertama (SMP)',
        'SMP' => 'Sekolah Menengah Pertama (SMP)',
        'P03' => 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)',
        'SMA' => 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)',
        'SMK' => 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)',
        'SMA/SMK' => 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)',
        'Sekolah Menengah Awal/Kejuruan (SMA/SMK)' => 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)',
        'P04' => 'Diploma I (D1)',
        'D1'  => 'Diploma I (D1)',
        'P05' => 'Diploma III (D3)',
        'D3'  => 'Diploma III (D3)',
        'P06' => 'Diploma IV (D4)',
        'D4'  => 'Diploma IV (D4)',
        'P07' => 'Sarjana (S1)',
        'S1'  => 'Sarjana (S1)',
        'P08' => 'Magister (S2)',
        'S2'  => 'Magister (S2)',
        'P09' => 'Doktoral (S3)',
        'S3'  => 'Doktoral (S3)',
        'Doktor (S3)' => 'Doktoral (S3)',
    ];

    public const POSITION_MAP = [
        'J001' => 'Kepala Yayasan',
        'J002' => 'Staff Yayasan',
        'J003' => 'Kepala Sekolah',
        'J004' => 'Guru',
        'J005' => 'Staff Sekolah',
        'J006' => 'Admin Sekolah',
    ];

    public const EMPLOYMENT_MAP = [
        'SK01' => 'Tetap',
        'SK02' => 'Kontrak',
        'SK03' => 'Honorer',
    ];

    public const OCCUPATION_MAP = [
        'pns'              => 'PNS',
        'bumn'             => 'BUMN',
        'wiraswasta'       => 'Wiraswasta',
        'pegawai swasta'   => 'Pegawai Swasta',
        'ibu rumah tangga' => 'Ibu Rumah Tangga',
        'lainnya'          => 'Lainnya',
    ];

    public static function normalizeByMap(?string $value, array $map, bool $upperLookup = true): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $trimmed = trim($value);
        $key = $upperLookup ? strtoupper($trimmed) : strtolower($trimmed);
        return $map[$trimmed] ?? $map[$key] ?? $trimmed;
    }

    public static function normalizeGenderToDb(?string $gender): ?string
    {
        if (!$gender) {
            return null;
        }
        return in_array(strtolower(trim($gender)), ['laki-laki', 'male', 'jk01', 'l'], true) ? 'male' : 'female';
    }

    public static function normalizeRelationshipToDb(?string $relation): ?string
    {
        if (!$relation) {
            return null;
        }
        return match (strtolower(trim($relation))) {
            'ayah', 'father'   => 'father',
            'ibu', 'mother'    => 'mother',
            'wali', 'guardian' => 'guardian',
            default            => trim($relation),
        };
    }

    public static function normalizeStudentStatusToDb(?string $status): string
    {
        if (!$status) {
            return 'active';
        }
        return match (strtolower(trim($status))) {
            '0', 'nonaktif', 'tidak aktif', 'expelled' => 'expelled',
            '2', 'pindah', 'transferred'               => 'transferred',
            '3', 'lulus', 'alumni'                     => 'alumni',
            default                                    => 'active',
        };
    }

    protected function gender(): Attribute
    {
        return Attribute::make(
            set: fn (?string $val) => self::normalizeGenderToDb($val),
        );
    }

    protected function religion(): Attribute
    {
        return Attribute::make(
            get: fn (?string $val) => self::normalizeByMap($val, self::AGAMA_MAP),
            set: fn (?string $val) => self::normalizeByMap($val, self::AGAMA_MAP),
        );
    }

    protected function maritalStatus(): Attribute
    {
        return Attribute::make(
            get: fn (?string $val) => self::normalizeByMap($val, self::MARITAL_MAP),
            set: fn (?string $val) => self::normalizeByMap($val, self::MARITAL_MAP),
        );
    }

    protected function lastEducation(): Attribute
    {
        return Attribute::make(
            get: fn (?string $val) => self::normalizeByMap($val, self::EDUCATION_MAP),
            set: fn (?string $val) => self::normalizeByMap($val, self::EDUCATION_MAP),
        );
    }

    protected function position(): Attribute
    {
        return Attribute::make(
            get: fn (?string $val) => self::normalizeByMap($val, self::POSITION_MAP),
            set: fn (?string $val) => self::normalizeByMap($val, self::POSITION_MAP),
        );
    }

    protected function employmentStatus(): Attribute
    {
        return Attribute::make(
            get: fn (?string $val) => self::normalizeByMap($val, self::EMPLOYMENT_MAP),
            set: fn (?string $val) => self::normalizeByMap($val, self::EMPLOYMENT_MAP),
        );
    }

    protected function occupation(): Attribute
    {
        return Attribute::make(
            get: fn (?string $val) => self::normalizeByMap($val, self::OCCUPATION_MAP, false),
            set: fn (?string $val) => self::normalizeByMap($val, self::OCCUPATION_MAP, false),
        );
    }

    protected function relationship(): Attribute
    {
        return Attribute::make(
            set: fn (?string $val) => self::normalizeRelationshipToDb($val),
        );
    }
}


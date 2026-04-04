<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SepayBank extends Model
{
    protected $fillable = [
        'name',
        'code',
        'bin',
        'short_name',
        'supported'
    ];

    protected $casts = [
        'supported' => 'boolean',
    ];

    /**
     * Scope để lấy các ngân hàng được hỗ trợ
     */
    public function scopeSupported($query)
    {
        return $query->where('supported', true);
    }

    /**
     * Tìm ngân hàng theo mã
     */
    public static function findByCode($code)
    {
        return static::where('code', $code)->first();
    }

    /**
     * Tìm ngân hàng theo BIN
     */
    public static function findByBin($bin)
    {
        return static::where('bin', $bin)->first();
    }

    /**
     * Lấy tên SePay từ mã ngân hàng
     */
    public function getSepayNameAttribute()
    {
        // Mapping từ mã ngân hàng sang tên SePay
        $sepayMapping = [
            'ICB' => 'VietinBank',
            'VCB' => 'Vietcombank',
            'MB' => 'MBBank',
            'ACB' => 'ACB',
            'VPB' => 'VPBank',
            'TPB' => 'TPBank',
            'MSB' => 'MSB',
            'LPB' => 'LienVietPostBank',
            'VCCB' => 'VietCapitalBank',
            'BIDV' => 'BIDV',
            'STB' => 'Sacombank',
            'VIB' => 'VIB',
            'HDB' => 'HDBank',
            'SEAB' => 'SeABank',
            'SHBVN' => 'ShinhanBank',
            'VBA' => 'Agribank',
            'TCB' => 'Techcombank',
            'BAB' => 'BacABank',
            'ABB' => 'ABBANK',
            'EIB' => 'Eximbank',
            'PBVN' => 'PublicBank',
            'OCB' => 'OCB',
            'KLB' => 'KienLongBank'
        ];

        return $sepayMapping[$this->code] ?? $this->short_name;
    }
}

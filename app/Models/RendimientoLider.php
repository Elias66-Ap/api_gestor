<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RendimientoLider extends Model
{
    use HasFactory;

    protected $table = 'rendimiento_lider';

    protected $fillable = [
        'id',
        'id_usu',
        'pro_creados',
        'pro_total',
        'pro_completados',
        'pro_tarde',
        'rendimiento',
    ];

    public $timestamps = false;

    public function lider(){
        $this->belongsTo(Perfil::class, 'id_usu');
    }

}

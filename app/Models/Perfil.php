<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    use HasFactory;

    protected $table = 'perfil';

    protected $fillable = [
        'id_usu',
        'nombre',
        'apellido',
        'apodo',
        'telefono',
        'fecha_nacimiento',
        'hobby',
        'habilidades'
    ];

    public $timestamps = false;

    public function usuario() {
        return $this->belongsTo(Usuario::class, 'id_usu');
    }
}

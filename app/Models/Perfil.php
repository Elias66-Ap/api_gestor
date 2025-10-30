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

    protected $primaryKey = 'id_usu';
    //public $incrementing = false;
    //protected $keyType = 'int';
    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usu');
    }

    public function mensajesEnviados()
    {
        return $this->hasMany(Mensaje::class, 'id_remitente');
    }

    public function mensajesRecibidos()
    {
        return $this->hasMany(Mensaje::class, 'id_destinatario');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensaje';
    protected $fillable = [
        'asunto',
        'contenido',
        'visto',
        'fecha_envio',
        'id_remitente',
        'id_destinatario'
    ];

    public $timestamps = false;

    public function remitente() {
        return $this->belongsTo(Perfil::class, 'id_remitente');
    }

    public function destinatario() {
        return $this->belongsTo(Perfil::class, 'id_destinatario');
    }
}

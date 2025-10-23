<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuario';

    protected $fillable = [
        'id',
        'correo',
        'rol',
        'passwordd',
        'tiene_perfil',
        'estado',
    ];
    
    protected $hidden = ['passwordd'];

    public $timestamps = false;


    public function perfil()
    {
        return $this->hasOne(Perfil::class, 'id_usu');
    }

    public function rendimiento()
    {
        return $this->hasOne(Rendimiento::class, 'id_usu');
    }

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class, 'id_creador');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_asignado');
    }

    public function mensajesEnviados()
    {
        return $this->hasMany(Mensaje::class, 'id_remitente');
    }

    public function mensajesRecibidos()
    {
        return $this->hasMany(Mensaje::class, 'id_destinatario');
    }

    public function isAdmin()
    {
        return $this->rol == 'Administrador';
    }

    public function isLider()
    {
        return $this->rol == 'Lider';
    }

    public function isColaborador()
    {
        return $this->rol == 'Colaborador';
    }
}

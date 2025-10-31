<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\MiembroProyectoController;
use App\Http\Controllers\ContenidoController;
use App\Http\Controllers\DashboardController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Usuarios
Route::get('/lideres', [UsuarioController::class, 'lideres']);
Route::get('/colaboradores', [UsuarioController::class, 'colaboradores']);
Route::post('/completar-perfil/{id}', [PerfilController::class, 'completarPerfil']);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('perfiles', PerfilController::class);

//proyectos
Route::patch('/completar/proyecto/{id}', [ProyectoController::class, 'completarProyecto']);
Route::patch('pausar/proyecto/{id}', [ProyectoController::class, 'pausarProyecto']);
Route::get('/proyecto/total', [ProyectoController::class, 'totalProyectos']);
Route::apiResource('proyectos', ProyectoController::class);

//Dashboard
Route::get('/dashboard/inicio', [DashboardController::class, 'dashboard']);
Route::get('/usuarios/por/mes', [DashboardController::class, 'usuariosPorMes']);
Route::get('/dashboard/proyectos', [DashboardController::class, 'proyectosChart']);

//tareas
Route::apiResource('tareas', TareaController::class);
Route::get('tareas/usuario/{id_asignado}', [TareaController::class, 'tareasUsuario']);
Route::get('tareas/proyecto/{id_pro}', [TareaController::class, 'tareasProyecto']);
Route::patch('/tareas/{id}/estado', [TareaController::class, 'cambiarEstadoColaborador']);
Route::patch('/tareas/{id}/revision', [TareaController::class, 'cambiarEstadoLider']);

Route::patch('/tareas/{id}/status', [TareaController::class, 'cambiarEstado']);

Route::post('tareas/{id_tarea}/contenido', [ContenidoController::class, 'agregarContenido']);
Route::patch('/contenidos/{id}/editar', [ContenidoController::class, 'updateContenido']);
Route::delete('/contenidos/{id}/eliminar', [ContenidoController::class, 'destroyContenido']);


Route::apiResource('mensajes', MensajeController::class);
Route::apiResource('miembrosproyecto', MiembroProyectoController::class);

Route::get('/mi-perfil/{id}', [PerfilController::class, 'miPerfil']);
Route::patch('/editar-perfil/{id}', [PerfilController::class, 'editarPerfil']);

//Mensajes
Route::get('/mensajes-usuario/{id}', [MensajeController::class, 'mostrarMensajes']);
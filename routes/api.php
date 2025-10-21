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


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('perfiles', PerfilController::class);
Route::apiResource('proyectos', ProyectoController::class);

Route::get('tareas/activas', [TareaController::class, 'tareasActivas']);
Route::get('tareas/completas', [TareaController::class, 'tareasCompletas']);

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

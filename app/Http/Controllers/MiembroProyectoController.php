<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MiembroProyecto;
use App\Models\Tarea;
use App\Models\Proyecto;
use App\Models\Usuario;
use App\Models\Perfil;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MiembroProyectoController extends Controller
{
    public function index()
    {
        return response()->json(MiembroProyecto::all(), 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_proyecto' => 'required|integer',
            'id_usuarios' => 'required|array',
            'id_usuarios.*' => 'integer|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $miembrosCreados = [];

        foreach ($request->id_usuarios as $id_usuario) {
            // Obtener el rol del usuario desde la tabla usuarios
            $usuario = Usuario::find($id_usuario);
            if (!$usuario) continue; // Ignorar si no existe

            $miembrosCreados[] = MiembroProyecto::create([
                'id_proyecto' => $request->id_proyecto,
                'id_usuario' => $id_usuario,
                'rol' => $usuario->rol, // <-- se toma automáticamente
            ]);
        }

        return response()->json([
            'status' => 'success',
            'miembros' => $miembrosCreados
        ], 201);
    }

    public function agregarMiembros(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_proyecto' => 'required|integer',
            'id_usuarios' => 'required|array',
            'id_usuarios.*' => 'integer|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'success',
                'error' => $validator->errors()
            ], 422);
        }

        $miembros = [];

        foreach ($request->id_usuarios as $id) {
            $usuario = Usuario::find($id);
            if (!$usuario) continue;

            $miembros[] = MiembroProyecto::create([
                'id_proyecto' => $request->id_proyecto,
                'id_usuario' => $id,
                'rol' => $usuario->rol
            ]);
        }

        return response()->json([
            'status' => 'success',
            'miembros' => $miembros,
        ]);
    }

    // Mostrar un miembro específico
    public function show($id)
    {
        $miembro = MiembroProyecto::find($id);
        if (!$miembro) {
            return response()->json(['message' => 'Miembro no encontrado'], 404);
        }
        return response()->json($miembro, 200);
    }

    // Actualizar un miembro
    public function update(Request $request, $id)
    {
        $miembro = MiembroProyecto::find($id);
        if (!$miembro) {
            return response()->json(['message' => 'Miembro no encontrado'], 404);
        }

        $miembro->update($request->all());
        return response()->json($miembro, 200);
    }

    // Eliminar un miembro
    public function destroy($id)
    {
        $miembro = MiembroProyecto::find($id);
        if (!$miembro) {
            return response()->json(['message' => 'Miembro no encontrado'], 404);
        }

        $miembro->delete();
        return response()->json(['message' => 'Miembro eliminado correctamente'], 200);
    }

    public function proyectosUsuario($id)
    {
        $hoy = Carbon::now()->toDateString();

        $p = MiembroProyecto::where('id_usuario', $id)->pluck('id_proyecto');
        $projects = Proyecto::whereIn('id', $p)->with('miembros.miembros')->get();

        $tareas = Tarea::where('id_asignado', $id)->where('estado', '!=', 'Hecho')
            ->where('fecha_vencimiento', '>=', $hoy)
            ->orderBy('fecha_vencimiento', 'asc') // asc = fecha más cercana primero
            ->get();

        return response()->json([
            'status' => 'success',
            'proyectos' => $projects,
            'tareas' => $tareas
        ], 202);
    }


    public function verProyecto($id)
    {
        $miembros = MiembroProyecto::where('id_proyecto', $id)->pluck('id_usuario');
        $usuarios = Perfil::whereIn('id_usu', $miembros)->get();

        $proyecto = Proyecto::with('tareas')->find($id);

        return response()->json([
            'status' => 'success',
            'proyecto' => $proyecto,
            'miembros' => $usuarios
        ]);
    }
}

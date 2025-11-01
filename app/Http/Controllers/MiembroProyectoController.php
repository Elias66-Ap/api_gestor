<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MiembroProyecto;
use App\Models\Tarea;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;

class MiembroProyectoController extends Controller
{
    // Obtener todos los miembros de proyectos
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

    public function agregarMiembros(Request $request, $idPro)
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

        foreach ($request->id_usuario as $id) {
            $usuario = Usuario::find($id);
            if (!$usuario) continue;

            $miembros[] = MiembroProyecto::create([
                'id_proyecto' => $idPro,
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
        $proyectos = Proyecto::whereIn('id', function ($query) use ($id) {
            $query->select('id_proyecto')
                ->from('miembros_proyecto')
                ->where('id_usuario', $id);
        })->get();

        $tareas = Tarea::where('id_asignado', $id)->get();

        return response()->json([
            'status' => 'success',
            'proyectos' => $proyectos,
            'tareas' => $tareas
        ], 202);
    }

    public function miembrosProyecto($id)
    {
        $miembros = Proyecto::with('miembros')->find($id);

        return response()->json([
            'status' => 'success',
            'miembros' => $miembros
        ], 202);
    }


}

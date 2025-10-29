<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Miembroproyecto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::with('tareas')
        ->withCount('miembros')->get();

        return response()->json([
            'status' => 'success',
            'proyectos' => $proyectos
        ], 200);
    }

    // Crear un nuevo proyecto
    public function store(Request $request)
    {
        // Validar datos del proyecto y usuarios
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_entrega' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:' . now(),
            'id_creador' => 'required|integer',
            'id_usuarios' => 'required|array|min:2',       // usuarios obligatorios
            'id_usuarios.*' => 'required|integer|distinct',
        ], [
            'id_usuarios.required' => 'Debe seleccionar al menos dos usuarios',
            'id_usuarios.*.required' => 'Usuario inválido',
            'id_usuarios.*.distinct' => 'Usuarios duplicados',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Crear el proyecto
        $proyecto = Proyecto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_entrega' => $request->fecha_entrega,
            'id_creador' => $request->id_creador
        ]);

        $miembrosCreados = [];

        // Asignar usuarios al proyecto si vienen en la solicitud
        if ($request->filled('id_usuarios')) {
            foreach ($request->id_usuarios as $id_usuario) {
                $usuario = Usuario::find($id_usuario);
                if (!$usuario) continue;

                $miembrosCreados[] = Miembroproyecto::create([
                    'id_proyecto' => $proyecto->id,
                    'id_usuario' => $id_usuario,
                    'rol' => $usuario->rol,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto creado con usuarios asignados',
            'proyecto' => $proyecto,
            'miembros' => $miembrosCreados
        ], 201);
    }

    public function completarProyecto($id)
    {
        $proyecto = Proyecto::find($id);

        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto inexistente'
            ], 404);
        }

        $proyecto->completado = 1;
        $proyecto->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto completado',
            'proyecto' => $proyecto
        ], 202);
    }

    // Mostrar un proyecto específico
    public function show($id)
    {
        $proyecto = Proyecto::with('tareas')->find($id);

        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'proyecto' => $proyecto
        ], 200);
    }

    // Actualizar un proyecto
    public function update(Request $request, $id)
    {
        $proyecto = Proyecto::find($id);
        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto no encontrado'
            ], 404);
        }

        $proyecto->update($request->all());
        return response()->json([
            'status' => 'success',
            'success' => 'Proyecto actualizado',
            'proyecto' => $proyecto
        ], 200);
    }

    // Eliminar un proyecto
    public function destroy($id)
    {
        $proyecto = Proyecto::find($id);
        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto no encontrado'
            ], 404);
        }

        $proyecto->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto eliminado correctamente'
        ], 200);
    }

    public function totalProyectos()
    {
        $total = Proyecto::where('completado', '=', 0)->count();

        return response()->json([
            'status' => 'success',
            'data' => $total
        ], 200);
    }
}

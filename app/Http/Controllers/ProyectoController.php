<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Miembroproyecto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::with('tareas')
        ->where('completado', '!=', 2)
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
            'descripcion_breve' => 'required|string|max:500',
            'descripcion_detalle' => 'nullable|string',
            'fecha_entrega' => 'nullable|date_format:Y-m-d H:i|after_or_equal:' . now(),
            'id_creador' => 'required|integer',
            'id_lider' => 'required|integer',
        ], [
            'nombre.required' => 'El nombre del proyecto es obligatorio',
            'descripcion_breve.required' => 'La descripción breve es obligatoria',
            'id_lider.required' => 'Seleccione el lider del proyecto',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $proyecto = Proyecto::create([
            'nombre' => $request->nombre,
            'descripcion_breve' => $request->descripcion_breve,
            'descripcion_detalle' => $request->descripcion_detalle,
            'fecha_entrega' => $request->fecha_entrega,
            'id_creador' => $request->id_creador
        ]);

        $id = $request->id_lider;
        $usuario = Usuario::find($id);
        
        $lider = Miembroproyecto::create([
            'id_proyecto' => $proyecto->id,
            'id_usuario' => $id,
            'rol' => $usuario->rol,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto creado',
            'proyecto' => $proyecto,
            'lider' => $lider
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

    public function pausarProyecto($id){
        $proyecto = Proyecto::find($id);

        $proyecto->completado = 2;
        $proyecto->fecha_completado = now();
        $proyecto->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto pausado',
            'proyecto' => $proyecto
        ]);
    }

    
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Tarea;
use App\Models\TareaContenido;
use Illuminate\Support\Facades\Storage;

class ContenidoController extends Controller
{
    public function agregarContenido(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_tarea' => 'required|integer',
            'archivos.*' => 'nullable|file|max:10240', // máximo 10MB
            'texto.*' => 'string'
        ], [
            'archivos.*.file' => 'Cada archivo debe ser válido',
            'archivos.*.max' => 'Cada archivo no puede superar 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarea = Tarea::find($request->id_tarea);

        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        // Guardar archivos subidos
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {

                $nombreOriginal = $archivo->getClientOriginalName(); // ej: "Trabajo_Final.docx"


                $ruta = $archivo->storeAs('tareas', $nombreOriginal, 'public');

                TareaContenido::create([
                    'id_tarea' => $tarea->id,
                    'tipo' => 'archivo',
                    'valor' => $ruta   
                ]);
            }
        }

        // Guardar textos
        if ($request->filled('texto')) {

            $html = $request->texto;                      // lo que manda Quill, ej: "<p>github.com</p><p><br></p>"
            $plain = trim(strip_tags($html));             // sacar etiquetas y espacios

            if ($plain !== '') { // solo si hay algo real
                TareaContenido::create([
                    'id_tarea' => $tarea->id,
                    'tipo'     => 'texto',
                    'valor'    => $html,
                ]);
            }
        }


        $tarea->load('contenidos');

        return response()->json([
            'status' => 'success',
            'message' => 'Contenido agregado correctamente',
            'data' => $tarea
        ], 201);
    }


    public function updateContenido(Request $request, $id_contenido)
    {
        $contenido = TareaContenido::find($id_contenido);

        if (!$contenido) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contenido no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'valor' => 'required|string',
        ], [
            'valor.required' => 'El valor del contenido es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $contenido->valor = $request->valor;
        $contenido->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Contenido actualizado correctamente',
            'data' => $contenido
        ]);
    }

    public function eliminarContenido($id)
    {
        $contenido = TareaContenido::find($id);

        if ($contenido->tipo === 'archivo') {
            Storage::disk('public')->delete($contenido->valor);
        }

        $contenido->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contenido eliminado correctamente'
        ], 200);
    }

    public function descargarArchivo($id)
    {
        $archivo = TareaContenido::findOrFail($id);

        $ruta = storage_path('tareas/' . $archivo->ruta); // ejemplo: storage/app/uploads/tareas/xxx.pdf

        if (!file_exists($ruta)) {
            return response()->json(["error" => "Archivo no encontrado"], 404);
        }

        return response()->download($ruta, $archivo->nombre_original);
    }

    public function descargar(TareaContenido $contenido)
    {
        if ($contenido->tipo !== 'archivo') {
            abort(404);
        }

        // valor correcto según tu BD
        $ruta = $contenido->valor;

        return response()->download(
            storage_path('tareas/' . $ruta),
            $contenido->nombre_original
        );
    }
}

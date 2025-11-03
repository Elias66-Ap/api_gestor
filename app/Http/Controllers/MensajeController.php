<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MensajeController extends Controller
{
    public function index()
    {
        $mensajes = Mensaje::with(['remitente', 'destinatario'])->get();
        return response()->json([
            'status' => 'success',
            'mensaje' => $mensajes
        ]);
    }

    public function mostrarMensajes($id)
    {
        $enviados = Mensaje::with('destinatario')->where('id_remitente', $id)->orderBy('fecha_envio', 'desc')->get();
        $recibidos = Mensaje::with('remitente')->where('id_destinatario', $id)->orderBy('fecha_envio', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'enviados' => $enviados,
                'recibidos' => $recibidos
            ],
        ], 200);
    }

    public function mensajesUsuarios($idr, $idd)
    {
        $mensaje = Mensaje::where('id_remitente',  'id_destinatario', $idr, $idd)->get();

        if ($mensaje->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron mensaje para este usuario',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'mensajes' => $mensaje
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'asunto' => 'required|string|max:200',
                'contenido' => 'required|string',
                'id_remitente' => 'required|integer',
                'id_destinatario' => 'required|array|min:1',
                'id_destinatario.*' => 'integer|distinct',
            ],
            [
                'asunto.required' => "El campo asunto es obligatorio",
                'contenido.required' => "No hay contenido en el mensaje",
                'id_destinatario.required' => 'Mensaje sin destinatario',
                'id_destinatario.min' => 'Debe haber al menos un destinatario'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $mensajes = [];

        foreach ($request->id_destinatario as $ids) {
            $mensajes = Mensaje::create([
                'asunto' => $request->asunto,
                'contenido' => $request->contenido,
                'fecha_envio' => now(),
                'id_remitente' => $request->id_remitente,
                'id_destinatario' => $ids
            ]);
        }

        return response()->json([
            'status' => 'success',
            'success' => 'Mensaje enviado',
            'mensaje' => $mensajes
        ], 201);
    }

    public function show($id)
    {
        $mensaje = Mensaje::find($id);
        if (!$mensaje) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'success' => $mensaje
        ]);
    }

    public function update(Request $request, $id)
    {
        $mensaje = Mensaje::find($id);
        if (!$mensaje) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        $mensaje->update($request->all());

        return response()->json([
            'status' => 'success',
            'success' => 'Mensaje actualizado',
            'mensaje' => $mensaje
        ]);
    }

    public function destroy($id)
    {
        $mensaje = Mensaje::find($id);

        if (!$mensaje) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        $mensaje->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Mensaje eliminado correctamente'
        ]);
    }

    public function mensajeVisto($id)
    {
        $mensaje = Mensaje::find($id);

        $mensaje->visto = 1;
        $mensaje->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Mensaje visto'
        ]);
    }
}

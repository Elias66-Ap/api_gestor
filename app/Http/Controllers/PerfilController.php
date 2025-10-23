<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perfil;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;

class PerfilController extends Controller
{
    public function index()
    {
        return Perfil::all();
    }

    public function store(Request $request)
    {
        $user = Auth::guard('usuario')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:200',
            'apodo' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|min:9',
            'fecha_nacimiento' => 'nullable|date',
            'hobby' => 'nullable|string',
            'habilidades' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $imagenPath = $request->file('imagen')->store('perfil', 'public');
        }

        $perfil = Perfil::create([
            'id_usu' => $user->id,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'apodo' => $request->apodo,
            'telefono' => $request->telefono,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'hobby' => $request->hobby,
            'habilidades' => $request->habilidades,
            'imagen' => $imagenPath,
        ]);

        $user->estado = 1;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil creado correctamente.',
            'perfil' => $perfil
        ], 201);
    }

    public function show($id)
    {
        $perfil = Perfil::where('id_usu', $id)->get();
        return response()->json($perfil);
    }

    public function update(Request $request, $id)
    {
        $perfil = Perfil::where('id_usu', $id)->first();

        if (!$perfil) {
            return response()->json([
                'status' => 'error',
                'message' => 'Perfil no encontrado.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:100',
            'apellido' => 'sometimes|string|max:200',
            'apodo' => 'sometimes|string|max:50',
            'telefono' => 'sometimes|string|min:9',
            'fecha_nacimiento' => 'sometimes|date',
            'hobby' => 'sometimes|string',
            'habilidades' => 'sometimes|string',
            'imagen' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'apellido.string' => 'El apellido debe contener solo texto válido.',
            'apellido.max' => 'El apellido no debe superar los 200 caracteres.',
            'apodo.string' => 'El apodo debe contener solo texto válido.',
            'apodo.max' => 'El apodo no debe superar los 50 caracteres.',
            'telefono.min' => 'El teléfono debe tener al menos 9 caracteres.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe tener un formato válido (YYYY-MM-DD).',
            'hobby.string' => 'El hobby debe contener solo texto válido.',
            'habilidades.string' => 'Las habilidades deben contener solo texto válido.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe estar en formato: jpeg, png, jpg o gif.',
            'imagen.max' => 'La imagen no debe superar los 2 MB de tamaño.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $imagenPath = $request->file('imagen')->store('perfil', 'public');
        }

        $perfil->update($request->only([
            'nombre',
            'apellido',
            'apodo',
            'telefono',
            'fecha_nacimiento',
            'hobby',
            'habilidades',
            'imagen',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $perfil
        ]);
    }

    public function destroy($id)
    {
        $perfil = Perfil::findOrFail($id);
        $perfil->delete();
        return response()->json(null, 204);
    }
}

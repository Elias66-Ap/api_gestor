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
            'telefono' => 'nullable|string|max:15',
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
        $perfil = Perfil::findOrFail($id);
        return response()->json($perfil);
    }

    public function update(Request $request, $id)
    {
        $perfil = Perfil::findOrFail($id);
        $perfil->update($request->all());
        return response()->json($perfil);
    }

    public function destroy($id)
    {
        $perfil = Perfil::findOrFail($id);
        $perfil->delete();
        return response()->json(null, 204);
    }
}

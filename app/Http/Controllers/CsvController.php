<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class CsvController extends Controller
{
    /**
     * Lista todos los ficheros CSV de la carpeta storage/app.
     *
     * @return JsonResponse La respuesta en formato JSON.
     *
     * El JSON devuelto debe tener las siguientes claves:
     * - mensaje: Un mensaje indicando el resultado de la operación.
     * - contenido: Un array con los nombres de los ficheros.
     */
    public function index(): JsonResponse
    {
        $files = Storage::files();
        $csvFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        return response()->json([
            'mensaje' => 'Listado de ficheros',
            'contenido' => array_values($csvFiles),
        ]);
    }

   /**
     * Recibe por parámetro el nombre de fichero y el contenido CSV y crea un nuevo fichero con ese nombre y contenido en storage/app. 
     * Devuelve un JSON con el resultado de la operación.
     * Si el fichero ya existe, devuelve un 409.
     *
     * @param filename Parámetro con el nombre del fichero. Devuelve 422 si no hay parámetro.
     * @param content Contenido del fichero. Devuelve 422 si no hay parámetro.
     * @return JsonResponse La respuesta en formato JSON.
     *
     * El JSON devuelto debe tener las siguientes claves:
     * - mensaje: Un mensaje indicando el resultado de la operación.
     */
    public function store(Request $request): JsonResponse
    {
        $filename = $request->input('filename');
        $content = $request->input('content');

        if (!$filename || !$content) {
            return response()->json(['mensaje' => 'Parámetros inválidos'], 422);
        }

        if (Storage::exists($filename)) {
            return response()->json(['mensaje' => 'El fichero ya existe'], 409);
        }

        Storage::put($filename, $content);

        return response()->json(['mensaje' => 'Guardado con éxito']);
    }

    /**
     * Recibe por parámetro el nombre de un fichero CSV el nombre de fichero y devuelve un JSON con su contenido.
     * Si el fichero no existe devuelve un 404.
     * Hay que hacer uso lo visto en la presentación CSV to JSON.
     *
     * @param name Parámetro con el nombre del fichero CSV.
     * @return JsonResponse La respuesta en formato JSON.
     *
     * El JSON devuelto debe tener las siguientes claves:
     * - mensaje: Un mensaje indicando el resultado de la operación.
     * - contenido: El contenido del fichero si se ha leído con éxito.
     */
    public function show(string $id): JsonResponse
    {
        if (!Storage::exists("app/$id")) {
            return response()->json(['mensaje' => 'Fichero no encontrado'], 404);
        }

        $content = Storage::get("app/$id");
        $lines = explode("\n", $content);
        $headers = str_getcsv(array_shift($lines));
        $data = array_map(function ($line) use ($headers) {
            return array_combine($headers, str_getcsv($line));
        }, array_filter($lines));

        return response()->json([
            'mensaje' => 'Fichero leído con éxito',
            'contenido' => $data,
        ]);
    }

   /**
     * Recibe por parámetro el nombre de fichero, el contenido CSV y actualiza el fichero CSV. 
     * Devuelve un JSON con el resultado de la operación.
     * Si el fichero no existe devuelve un 404.
     * Si el contenido no es un JSON válido, devuelve un 415.
     * 
     * @param filename Parámetro con el nombre del fichero. Devuelve 422 si no hay parámetro.
     * @param content Contenido del fichero. Devuelve 422 si no hay parámetro.
     * @return JsonResponse La respuesta en formato JSON.
     *
     * El JSON devuelto debe tener las siguientes claves:
     * - mensaje: Un mensaje indicando el resultado de la operación.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if (!Storage::exists($id)) {
            return response()->json(['mensaje' => 'Fichero no encontrado'], 404);
        }

        $content = $request->input('content');

        if (!$content) {
            return response()->json(['mensaje' => 'Parámetros inválidos'], 422);
        }

        if (json_decode($content) === null) {
            return response()->json(['mensaje' => 'Contenido no válido'], 415);
        }

        Storage::put($id, $content);

        return response()->json(['mensaje' => 'Fichero actualizado exitosamente']);
    }

     /**
     * Recibe por parámetro el nombre de ficher y lo elimina.
     * Si el fichero no existe devuelve un 404.
     *
     * @param filename Parámetro con el nombre del fichero. Devuelve 422 si no hay parámetro.
     * @return JsonResponse La respuesta en formato JSON.
     *
     * El JSON devuelto debe tener las siguientes claves:
     * - mensaje: Un mensaje indicando el resultado de la operación.
     */
    public function destroy(string $id): JsonResponse
    {
        if (!Storage::exists("app/$id")) {
            return response()->json(['mensaje' => 'Fichero no encontrado'], 404);
        }

        Storage::delete("app/$id");

        return response()->json(['mensaje' => 'Fichero eliminado exitosamente']);
    }
}


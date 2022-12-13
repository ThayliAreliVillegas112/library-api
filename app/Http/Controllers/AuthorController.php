<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index(){
        // $response = $this->getResponseSuccess();
        // //$book = Book::all();
        // $book = Book::with('category', 'editorials')->get();  //estos nombres deben coincidir con lo que está en el modelo
        // $response["data"] = $book;
        // return $response;
        // return[
        //     "error" => false,
        //     "message" => "",
        //     "data" => $book
        // ];

        // $books = Book::orderBy('title')->get();
        // return response()->json([
        //     'message' => 'Successful query',
        //     'data' => $books,
        // ], 200);
        $authors = Author::all();
        $authors = Author::with('books' )->get();
        return $this->getResponse200($authors);
    }

    public function store(Request $request)
    {
        $authors = new Author();
        $authors->name = $request->name;
        $authors->first_surname = $request->first_surname;
        $authors->second_surname = $request->second_surname;
        if ($authors->save()) {
            if(isset($request->books)){
                foreach ($request->books as $item) {
                    $authors->books()->attach($item);
                }
            }

            return $this->getResponse201("author", "Created", $authors);
        } else {
            return $this->getResponse400();
        }
    }

    public function show($id)
    {
        $authors = Author::with('books')->where("id", $id)->first();
        if ($authors) {
            return $this->getResponse200($authors);
        }else{
            return $this->getResponse404();
        }
    }

    public function update(Request $request, $id)
    {
        $authors = Author::find($id);
        DB::beginTransaction(); //nos sirve para salir de una trancisión manualmente mediante un estado de error
        try {
            if ($authors) {
                $authors->name = $request->name;
                $authors->first_surname = $request->first_surname;
                $authors->second_surname = $request->second_surname;
                $authors->update();
                DB::commit(); // funciona con la parte de beginTransicin()
                return $this->getResponse201("Author", "Updated", $authors);
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500(["No se ha actualizado"]);
        }
    }


    public function destroy($id)
    {


        $authors = Author::find($id);
        if ($authors != null) {
            $authors->books()->detach();
            $authors->delete();
            return $this->getResponseDelete200("author");
        }else {
            return $this->getResponse404();
        }
    }
}

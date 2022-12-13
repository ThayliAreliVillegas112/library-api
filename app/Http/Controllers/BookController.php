<?php


namespace App\Http\Controllers;

use App\Models\Book; //IMPORTAR EL MODELO
use App\Models\BookDownloads;
use App\Models\BookReviews;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB; //ESTA IMPORTACIÓN ES PARA PODER IMPLEMENTAR UNA INTERSECCIÓN
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
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
        $books = Book::orderBy('title', 'asc')->get();
        $books = Book::with('category', 'authors','editorials' )->get();
        return $this->getResponse200($books);
    }

    public function store(Request $request)
    {
        try {
            $isbn = preg_replace('/\s+/', '\u0020', $request->isbn); //Remove blank spaces from ISBN
            $existIsbn = Book::where("isbn", $isbn)->exists(); //Check if a registered book exists (duplicate ISBN)
            if (!$existIsbn) { //ISBN not registered
                $book = new Book();
                $book->isbn = $isbn;
                $book->title = $request->title;
                $book->description = $request->description;
                $book->published_date = date('y-m-d h:i:s'); //Temporarily assign the current date
                $book->category_id = $request->category["id"];
                $book->editorial_id = $request->editorial["id"];
                $book->save();
                $bookDownload = new BookDownloads();
                $bookDownload->book_id = $book->id;
                $bookDownload->save();
                foreach ($request->authors as $item) { //Associate authors to book (N:M relationship)
                    $book->authors()->attach($item);
                }
                return $this->getResponse201('book', 'created', $book);
            } else {
                return $this->getResponse500(['The isbn field must be unique']);
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }

    public function show($id){
        // $book = Book::with('authors')->where("id", $id)->first();
        $book = Book::with('authors')->where('id', $id)->with('category')->with('editorials')->first();
        // $book = Book::find($id);
        if ($book) {
            return $this->getResponse200($book);
        }else{
            return $this->getResponse404();
        }
    }

    public function update(Request $request, $id){
        $book = Book::find($id);
        DB::beginTransaction();
        try {
            if ($book) {
                $isbn = trim($request->isbn);
                $isbnBook = Book::where('isbn', $isbn)->first();
                if (!$isbnBook || $isbnBook->id == $book->id) {
                    $book->isbn = $isbn;
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->published_date = date('y-m-d h:i:s');
                    $book->category_id = $request->category['id'];
                    $book->editorial_id = $request->editorial['id'];
                    $book->update();
                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item);
                    }
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }
                    DB::commit();
                    $book = Book::with('category', 'editorial', 'authors')->where('id', $id)->get();
                    return $this->getResponse201('book', 'updated', $book);
                } else {
                    return $this->getResponse400();
                }
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse201('book', 'updated', $book);
            // return $this->getResponse500([]);
        }
    }

    public function destroy($id){
        $book = Book::find($id);
        if ($book != null) {
            $book->authors()->detach();
            $id = $book->id;
            $bookDownload = BookDownloads::where('book_id', $id)->first();
            $bookDownload->delete();
            $book->delete();
            return $this->getResponseDelete200('book');
        }else {
            return $this->getResponse404();
        }
    }

    public function addBookReview(Request $request,$book_id){
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
            if (!$validator->fails()) {
                DB::beginTransaction();
                try{
                    $user = auth()->user();
                    $bookRew = new BookReviews();
                    $bookRew->comment = $request->comment;
                    $bookRew->book_id = $book_id;
                    $bookRew->user_id = $user->id;
                    $bookRew->save();
                    DB::commit();
                    return $this->getResponse201('book review','created',$bookRew);
                }catch (Exception $e){
                    DB::rollBack();
                    return $this->getResponse500([$e->getMessage()]);
                }
            } else {
                return $this->getResponse500([$validator->errors()]);
            }
    }


    public function updateBookReview(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
            if (!$validator->fails()) {
                //$CurTok = $request->user()->currentAccessToken()->get();
                DB::beginTransaction();
                try{
                    $bookRew = BookReviews::where('id',$id)->get()->first();
                    $user = auth()->user();
                    if($bookRew->user_id != $user->id){
                        return $this->getResponse403();
                    }else{
                        $bookRew->comment = $request->comment;
                        $bookRew->edited = true;
                        $bookRew->update();
                        DB::commit();
                        return $this->getResponse201('book review','updated',$bookRew);
                    }

                }catch (Exception $e){
                    DB::rollBack();
                    return $this->getResponse404([$e->getMessage()]);
                }
            } else {
                return $this->getResponse500([$validator->errors()]);
            }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\Book;
use App\Models\User;
use App\Mail\SendMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class BookController extends Controller
{
    // retrieve all books
    public function getinputEmail() {
        return view('emails.input-email');
    }
    public function postinputEmail(Request $req) {
        $email=$req->email;
        //validate

        // kiểm tra có user có email như vậy không
        $user=User::where('email',$email)->get();
        // dd($user);
        if($user->count()!=0){
            // gửi mật khẩu reset tới email
            $matkhaumoi = substr( md5( rand (0,999999)), 0, 8); 
            $sentData = [
                'title' => 'Mật khẩu mới của bạn là:',
                'body' => $matkhaumoi,
            ];
            DB::table('users')->where('email',$email)->update([
                'password' =>   Hash::make($matkhaumoi) ,
                
               
            ]);
            Mail::to($req->user())->cc($email)->bcc($email)->send(new SendMail($sentData));
            Session::flash('message', 'Send email successfully!');

            return redirect()->route('getinputEmail'); //về lại trang đăng nhập của khách
        }
        else {
              return redirect()->route('getinputEmail')->with('message','email không tồn tại');
        }
    }
    public function readAll(Request $request)
    {
        $books = Book::all();
        foreach ($books as $book) {
            $book->categories = json_decode($book->categories);
        }
        return response()->json($books);
    }

    // retrieve one book
    public function readOne(Request $request, $id)
    {
        $book = Book::find($id);
        if ($book) {
            $book->categories = json_decode($book->categories);
            return response()->json($book);
        } else {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }

    // rules for validation
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['required', 'string', 'max:255'],
            'pages' => ['required', 'integer', 'min:1'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'distinct', 'max:255',],
            'image' => ['required', 'string', 'url'],
            'status' => ['required', Rule::in(['AVAILABLE', 'BORROWED', 'LOST'])],
        ];
    }

    // create new book
    public function create(Request $request)
    {
        // validate request
        $validator = Validator::make($request->only(['title', 'author', 'publisher', 'pages', 'categories', 'image', 'status']), $this->rules());
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $validated = $validator->validated();

        // check if book already exists
        $book = Book::where('title', $validated['title'])->first();
        if ($book) {
            return response()->json(['message' => 'Book already exists'], 400);
        }

        // json categories
        $validated['categories'] = json_encode($validated['categories']);

        // create new book
        $book = Book::create($validated);

        // return error message if failed
        if (!$book) {
            return response()->json(['message' => 'Failed to create new book'], 500);
        }

        return response()->json($book, 201);
    }

    // update a book
    public function update(Request $request, $id)
    {
        // validate request
        $validator = Validator::make($request->only(['title', 'author', 'publisher', 'pages', 'categories', 'image', 'status']), $this->rules());
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $validated = $validator->validated();

        $book = Book::find($id);
        if ($book) {
            // update book
            $book->update($validated);

            return response()->json($book, 200);
        } else {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }

    // delete a book
    public function delete($id)
    {
        $book = Book::find($id);
        if ($book) {
            $book->delete();
            return response()->json(['message' => 'Book deleted'], 200);
        } else {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }
}

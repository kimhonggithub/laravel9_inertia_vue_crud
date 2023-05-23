<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Book::query()->paginate(20);

        return Inertia::render('books', [
            'data' => $data
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBookRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBookRequest $request)
    {
        Validator::make($request->all(), [
            'title' => 'required',
            'author' => 'required'
        ])->validate();

        $book = Book::create($request->only(['title', 'author']));

        $this->processImage($request, $book);

        return redirect()->back()
            ->with('message', 'Book created');
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBookRequest  $request
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        Validator::make($request->all(), [
            'title' => 'required',
            'author' => 'required'
        ])->validate();

        $book->update($request->only(['title', 'author']));

        $this->processImage($request, $book); 

        return redirect()->back()
            ->with('message', 'Book updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function destroy(Book $book)
    {
        $book->delete();
        return redirect()->back()
            ->with('message', 'Book deleted');
    }

    public function upload(\Illuminate\Http\Request $request)
    {
        if($request->hasFile('imageFilepond'))
        {
            return $request->file('imageFilepond')->store('uploads/books', 'public');
        }
        return '';
    }

    public function uploadRevert(Request $request)
    {
        if($image = $request->get('image')) {
            $path = storage_path('app/public/' . $image);
            if(file_exists($path)){
                unlink($path);
            }
        }
        return '';
    }

    protected function processImage(Request $request, Book $book = null)
    {

        $images = $request->get('image') ? explode('|', $request->get('image')) : [];

        foreach($images as $image)
        {
            if(!$book->hasImage($image)){
                $path = storage_path('app/public/uploads/books/'.$image);
                if(file_exists($path)){
                    copy($path, public_path($image));
                    unlink($path);
                }
            }
        }

        foreach($book->findMissingImages($images) as $img)
        {
            if(file_exists(public_path($img))){
                unlink(public_path($img));
            }
        }

        $book->update([
            'image' => $request->get('image')
        ]);
    }
}

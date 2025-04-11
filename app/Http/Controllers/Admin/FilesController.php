<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Intervention\Image\Laravel\Facades\Image;

class FilesController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    protected function getFilesFromStorage($path)
    {
        $directories = Storage::directories($path);
        $files = Storage::files($path);

        return [
            $files,
            $directories,
            $path,
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $path = $request->path ?? 'public/files';

        // # If path doesn't have public/files in it, change it to public/files

        if (! str_contains($path, 'public/files')) {
            $path = 'public/files';
        }

        // Check if can view directory
        if (! $request->user()->can('viewAny', [File::class, $path])) {

            // If not, redirect to padaliniai/{padalinys}
            if ($this->authorizer->getTenants()->count() > 0) {
                $path = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;
            } else {
                // Redirect to dashboard home
                return redirect()->route('dashboard');
            }
        }

        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

        return Inertia::render('Admin/Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

    public function getFiles(Request $request)
    {
        $path = $request->path ?? 'public/files';

        if (! str_contains($path, 'public/files')) {
            $path = 'public/files';
        }

        // Check if can view directory
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {

            // If not, redirect to padaliniai/{padalinys}
            if ($this->authorizer->getTenants()->count() > 0) {
                $path = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;
            } else {
                // Return error response
                return response()->json([
                    'error' => 'You do not have permission to view this directory.',
                ], 403);
            }
        }

        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

        return response()->json([
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFilesRequest $request)
    {
        $validated = $request->validated();

        $files = $validated['files'];
        $path = $validated['path'];

        // Initialize an array to store any error messages
        $errors = [];

        // Check if file exists, if so, add a timestamp to the filename
        // Limit file size to 50 MB
        foreach ($files as $fileContainer) {
            $file = $fileContainer['file'];
            if ($file->getSize() > 50000000) {
                $errors[] = "File {$file->getClientOriginalName()} is too large. Maximum file size is 50 MB.";
            }
        }

        // If there are errors, return them
        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        foreach ($files as $fileContainer) {
            $file = $fileContainer['file'];

            if (Storage::exists($path.'/'.$file->getClientOriginalName())) {
                $file->storeAs($path, time().'_'.$file->getClientOriginalName());
            } else {
                $file->storeAs($path, $file->getClientOriginalName());
            }
        }

        // Return redirect to files index
        return back();
    }

    public function createDirectory(Request $request)
    {
        $path = $request->input('path');
        $name = $request->input('name');

        // Remove 'public' only from the start of the path, because it's already in the 'public' fs path
        $path = str_replace('public/', '', $path);

        // check if directory exists
        if (! Storage::exists($path.'/'.$name)) {
            Storage::disk('public')->makeDirectory($path.'/'.$name);
        }

        // return redirect to files index
        return back();
    }

    public function uploadImage(Request $request)
    {
        // Images can be uploaded as 1. files or as 2. data urls

        $data = $request->file()['file'] ?? $request->image;
        $originalName = isset($request->file()['file']) ? $request->file()['file']->getClientOriginalName() : $request->name;

        $startingImage = Image::read($data);

        $image = $startingImage->scaleDown(width: 1600)->toWebp();

        $path = (string) $request->input('path');

        // get file name without extension
        $originalName = pathinfo($originalName, PATHINFO_FILENAME).'.webp';

        // check if path exists
        if (! Storage::exists('public/'.$path)) {
            Storage::makeDirectory('public/'.$path);
        }

        // save image to storage
        // check if image exists
        if (Storage::exists('public/'.$path.'/'.$originalName)) {
            $originalName = time().'_'.$originalName;
        }

        $image->save(storage_path('app/public/'.$path.'/'.$originalName), 85);

        // return xhr response with image path
        return response()->json([
            'url' => '/uploads/'.$path.'/'.$originalName,
        ]);
    }

    public function delete(Request $request)
    {
        $path = $request->input('path');

        if (! str_contains($path, 'public/files')) {
            // Return with error
            return response()->json([
                'error' => 'You do not have permission to delete this file.',
            ], 403);
        }

        if (! $request->user()->can('deleteDirectory', $path)) {

            // If not, redirect to padaliniai/{padalinys}
            if ($this->authorizer->getTenants()->count() > 0) {
                $path = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;
            } else {
                // Redirect to dashboard home
                return redirect()->route('dashboard');
            }
        }

        // check if file exists
        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        // return redirect to files index
        return back();
    }
}

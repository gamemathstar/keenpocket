<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FirebaseController extends Controller
{
    public function showUploadForm()
    {
        $targetPath = storage_path('app/firebase/service-account.json');
        return view('firebase.upload', compact('targetPath'));
    }

    public function handleUpload(Request $request)
    {
        $request->validate([
            'key_file' => ['required','file','mimetypes:application/json,application/octet-stream,text/plain'],
        ]);

        $file = $request->file('key_file');

        // Ensure directory exists
        Storage::makeDirectory('firebase');

        // Save with a fixed name to simplify env configuration
        $savedPath = Storage::putFileAs('firebase', $file, 'service-account.json');

        $absolutePath = storage_path('app/'.$savedPath);

        return back()->with('status', 'Uploaded successfully. Set FIREBASE_CREDENTIALS to: '.$absolutePath);
    }
}



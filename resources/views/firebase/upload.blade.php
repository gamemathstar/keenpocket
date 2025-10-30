<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Firebase Service Account</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .row { margin-bottom: 16px; }
        .status { background: #ecfdf5; color: #065f46; padding: 10px 12px; border-radius: 6px; margin-bottom: 16px; border: 1px solid #a7f3d0; }
        .error { background: #fef2f2; color: #991b1b; padding: 10px 12px; border-radius: 6px; margin-bottom: 16px; border: 1px solid #fecaca; }
        label { display:block; margin-bottom: 8px; font-weight: 600; }
        input[type=file] { display:block; }
        button { background: #111827; color:#fff; border:0; padding:10px 14px; border-radius: 6px; cursor:pointer; }
        code { background:#f3f4f6; padding:2px 4px; border-radius:4px; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const form = document.getElementById('upload-form');
            form.addEventListener('submit', function () {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = token;
                form.appendChild(input);
            });
        });
    </script>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
</head>
<body>
    <div class="card">
        <h2>Upload Firebase Service Account JSON</h2>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="upload-form" action="{{ url('/firebase/upload') }}" method="post" enctype="multipart/form-data">
            <div class="row">
                <label for="key_file">Service account JSON file</label>
                <input id="key_file" name="key_file" type="file" accept="application/json,.json" required>
            </div>
            <div class="row">
                <button type="submit">Upload</button>
            </div>
        </form>

        <p>After upload, set in your <code>.env</code>:</p>
        <pre><code>FIREBASE_CREDENTIALS={{ $targetPath }}</code></pre>
    </div>
</body>
<!-- No inline comments added to explain actions -->
</html>



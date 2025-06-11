<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Email Verification Status</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen,
                Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f9fafb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 2rem 3rem;
            max-width: 400px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgb(0 0 0 / 0.1);
        }
        .icon {
            font-size: 48px;
            margin-bottom: 1rem;
        }
        .success {
            color: #16a34a;
            border-color: #bbf7d0;
            background-color: #dcfce7;
        }
        .error {
            color: #dc2626;
            border-color: #fecaca;
            background-color: #fee2e2;
        }
        .info {
            color: #2563eb;
            border-color: #bfdbfe;
            background-color: #dbeafe;
        }
        h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        p {
            margin: 0 0 1rem 0;
            font-size: 1rem;
            color: #4b5563;
        }
        a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    @php
        $status = $status ?? 'error';
    @endphp

    <div class="container
        @if($status === 'success') success
        @elseif($status === 'already_verified') info
        @else error @endif
    ">
        @if($status === 'success')
            <div class="icon">✅</div>
            <h1>Email Verified!</h1>
            <p>Your email has been successfully verified. You can now <a href="/">log in</a>.</p>
        @elseif($status === 'already_verified')
            <div class="icon">ℹ️</div>
            <h1>Already Verified</h1>
            <p>Your email was already verified. You can <a href="/">log in</a>.</p>
        @else
            <div class="icon">❌</div>
            <h1>Verification Failed</h1>
            <p>Invalid or expired verification link. Please try again or contact support.</p>
        @endif
    </div>
</body>
</html>

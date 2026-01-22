<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h1 class="h4 mb-3">Access your account</h1>
                    <p class="text-muted mb-4">
                        Enter your email to receive a one-time login link or start an anonymous session.
                    </p>

                    @if(session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('otp.send') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                class="form-control"
                                placeholder="you@example.com"
                                value="{{ old('email') }}"
                                required
                            >
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            Send access to email
                        </button>
                    </form>

                    <a href="{{ route('otp.anonymous') }}" class="btn btn-outline-secondary w-100" data-mac-link>
                        Log in anonymously
                    </a>

                    <p class="small text-muted mt-4 mb-0">
                        If the link does not arrive quickly, please check your junk or spam folder.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function resolveMacAddress() {
        if (window.localStorage) {
            let stored = localStorage.getItem('clientMacAddress');
            if (stored) {
                return stored;
            }
            stored = (crypto?.randomUUID ? crypto.randomUUID() : 'anon-' + Date.now());
            localStorage.setItem('clientMacAddress', stored);
            return stored;
        }
        return 'anon-' + Date.now();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var link = document.querySelector('[data-mac-link]');
        if (!link) {
            return;
        }
        var mac = resolveMacAddress();
        var url = new URL(link.href, window.location.origin);
        url.searchParams.set('mac_address', mac);
        link.href = url.toString();
    });
</script>
</body>
</html>

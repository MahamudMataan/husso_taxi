<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Medewerkerslogin | Husso Taxi</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="husso min-h-screen">
        <header class="site-header">
            <div class="husso-wrap site-header-inner">
                <x-husso.brand />
            </div>
        </header>
        <main class="staff-login">
            <section class="dispatch-panel dispatch-panel-body" aria-labelledby="login-title">
                <h1 id="login-title">Medewerkerslogin</h1>
                <x-auth-session-status class="husso-notice" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <label class="booking-field" for="login">
                        Gebruikersnaam of e-mail
                        <input id="login" class="husso-input" type="text" name="login" value="{{ old('login') }}" required autocomplete="username" @error('login') aria-invalid="true" aria-describedby="login-error" @enderror>
                    </label>
                    @error('login')
                        <p id="login-error" class="husso-notice husso-notice--error" role="alert">De gebruikersnaam of het wachtwoord klopt niet, of je hebt te vaak geprobeerd. Probeer het opnieuw.</p>
                    @enderror
                    <label class="booking-field" for="password">
                        Wachtwoord
                        <input id="password" class="husso-input" type="password" name="password" required autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    </label>
                    @error('password')
                        <p id="password-error" class="husso-notice husso-notice--error" role="alert">Vul je wachtwoord in.</p>
                    @enderror
                    <label class="staff-login-remember" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        Ingelogd blijven
                    </label>
                    <button class="husso-button husso-button--primary" type="submit">Inloggen</button>
                    @if (Route::has('password.request'))
                        <a class="dispatch-link" href="{{ route('password.request') }}">Wachtwoord vergeten?</a>
                    @endif
                </form>
            </section>
        </main>
    </body>
</html>

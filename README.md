# Autenticador_dois_fatores
## Serviu como base de implementação e estudo em um projeto real, usando a tecnologia PHP e o framework Laravel.
Antes precisamos entender  o que é um autenticador de dois fatores, lembrando que esse modelo é simples e que já atende bem a solicitação em restringir mais o acesso de login.
A autenticação de dois fatores é uma camada extra de proteção que pode ser ativada em contas online. Também conhecido pela sigla 2FA, originária do inglês "two-factor authentication", o recurso insere uma segunda verificação de identidade do usuário no momento do login, evitando o acesso às contas mesmo quando a senha é vazada.

## Uma view simples

```php
<div class="container mt-5">
            <div class="row">
                <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                    <div class="login-brand">
                        <img class="img-fluid logo-img" src="{{Storage::url('app/public/logo/'.$company_logo)}}" alt="image">
                    </div>
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 style="margin: 0 auto;color:white">{{__('Autenticação')}}</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('code.authentication.verify') }}">
                                @csrf
                                <div class="form-group">
                                    <label class="form-control-label" for="auth_code">Digite o código:</label>
                                    <input class="btn-block" id="auth_code" type="number" name="auth_code" required>
                                    @error('auth_code')
                                    <span class="invalid-email text-danger" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <button class="btn-primary btn-block" type="submit">Verificar Código</button>
                                </div>
                                <div class="form-group">
                                    <span><a href="/admin" style="display: grid; justify-content: space-around;font-size: 1.2rem">Voltar</a></span>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
```
## Funções para verificar a autenticação
### function adminLogin
```php
public function adminLogin(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        $superAdmin = \DB::table('users')
            ->where('email', $request->email)
            ->where('type', 'super admin')
            ->exists();

        $params = null;

        if ($superAdmin) {
            $params = [
                'email'    => $request->email,
                'type'     => 'super admin',
                'password' => $request->password,
            ];
        } else {
            $params = [
                'email'      => $request->email,
                'created_by' => \Utility::getEmploy('id'),
                'password'   => $request->password,
            ];
        }

        if (\Auth::attempt($params, $request->get('remember'))) {
            if (\Auth::user()->is_active == 0) {
                \Auth::logout();
            } elseif (\Auth::user()->type == 'super admin') {
                // Gerar e armazenar código de autenticação
                $authCode = random_int(100000, 999999);
                $user = Auth::user();
                $user->auth_code = $authCode;
                $user->auth_code_expire_at = Carbon::now()->addMinutes(5);
                $user->save();

                // Enviar o código de autenticação ao usuário (por exemplo, enviar um e-mail)
                $user->notify(new AuthenticationCodeNotification($authCode));

                //$user_code = $authCode;

                if (Auth::check(1)) {
                    session([
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_code' => $user->auth_code,
                    ]);

                    Auth::logout();
                }

                return redirect()->route('code.authentication');
            }else {
                return redirect()->route('dashboard');
            }
        }
        return $this->sendFailedLoginResponse($request);
    }
```
### function showCodeAuthenticationForm
```php
    public  function showCodeAuthenticationForm()
    {
        return view('auth.code_authentication_form');
    }
```
### function verifyCodeAuthentication
```php
public function verifyCodeAuthentication(Request $request)
    {

        $this->validate($request, [
            'auth_code' => 'required',
        ]);

        $userId = session('user_id');
        $userName = session('user_name');
        $userEmail = session('user_email');
        $userCode = session('user_code');

        // Verificar se o usuário existe

        $user = User::where('id', $userId)
            ->where('name', $userName)
            ->where('email', $userEmail)
            ->first();

        if ($user) {

            // Verificar se o código de autenticação não expirou
            if ($user->auth_code_expire_at && Carbon::parse($user->auth_code_expire_at)->gt(Carbon::now())) {
                // Verificar se o código digitado é igual ao que está no banco de dados
                if ($request->input('auth_code') == $user->auth_code) {
                    // Autenticar o usuário
                    Auth::loginUsingId($user->id, true);

                    $permiss = Role::findByName('super admin');
                    $user->assignRole($permiss);

                    return redirect()->intended('dashboard')->withErrors(['auth_code' => 'Autenticado com sucesso.']);

                } else {
                    // Código de autenticação inválido
                   // return redirect()->route('login')->withErrors(['auth_code' => 'Código de autenticação inválido.']);
                    return back()->withErrors(['auth_code' => 'Código de autenticação inválido.']);
                }
            } else {
                // Código de autenticação expirado
                //return redirect()->route('login')->withErrors(['auth_code' => 'Código de autenticação expirado.']);
                return back()->withErrors(['auth_code' => 'Código de autenticação expirado.']);
            }
        } else {
            // Usuário não encontrado
            return redirect()->route('admin')->withErrors(['auth_code' => 'Usuário não encontrado.']);

        }
    }
```

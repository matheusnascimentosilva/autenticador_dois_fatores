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


    public  function showCodeAuthenticationForm()
    {
        return view('auth.code_authentication_form');
    }

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

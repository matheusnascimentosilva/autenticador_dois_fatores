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

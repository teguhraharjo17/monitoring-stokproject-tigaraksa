<x-auth-layout>
    @section('title', 'Register')

    <style>
        .auth-form-title {
            font-size: 24px;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: 0.3px;
        }

        .auth-form-subtitle {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        .custom-input-group {
            position: relative;
        }

        .custom-input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 2;
        }

        .custom-input-group input,
        .custom-select {
            background-color: rgba(15, 23, 42, 0.7) !important;
            border: 1px solid #1e293b !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
            padding: 12px 14px 12px 42px !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
        }

        .custom-select {
            padding-left: 14px !important;
        }

        .custom-input-group input:focus,
        .custom-select:focus {
            border-color: #3b82f6 !important;
            background-color: rgba(15, 23, 42, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.22) !important;
        }

        .custom-input-group input:focus + .input-icon,
        .custom-input-group input:focus ~ .input-icon {
            color: #60a5fa !important;
        }

        .custom-input-group input::placeholder {
            color: #475569 !important;
            font-weight: 400 !important;
        }

        .btn-auth-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 12px 20px !important;
            border-radius: 10px !important;
            letter-spacing: 0.5px !important;
            box-shadow: 0 4px 18px rgba(37, 99, 235, 0.35) !important;
            transition: all 0.25s ease !important;
        }

        .btn-auth-primary:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5) !important;
            color: #ffffff !important;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            z-index: 2;
        }
    </style>

    <!--begin::Form-->
    <form class="form w-100" method="POST" novalidate="novalidate" id="kt_sign_up_form" data-kt-redirect-url="{{ route('login') }}" action="{{ route('admin.register') }}">
        @csrf
        
        <!--begin::Heading-->
        <div class="text-center mb-8">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.25);">
                <i class="bi bi-person-plus text-primary fs-2"></i>
            </div>
            <h1 class="auth-form-title mb-2">
                Sign Up
            </h1>
            <div class="auth-form-subtitle">
                Buat Akun Pengguna Baru
            </div>
        </div>
        <!--end::Heading-->

        <!--begin::Input group: Name-->
        <div class="fv-row mb-5">
            <div class="custom-input-group">
                <i class="bi bi-person-badge input-icon"></i>
                <input type="text" placeholder="Nama Lengkap" name="name" autocomplete="off" class="form-control"/>
            </div>
        </div>

        <!--begin::Input group: Username-->
        <div class="fv-row mb-5">
            <div class="custom-input-group">
                <i class="bi bi-person input-icon"></i>
                <input type="text" placeholder="Username" name="username" autocomplete="off" class="form-control"/>
            </div>
        </div>

        <!--begin::Role selection-->
        <div class="fv-row mb-5">
            <select name="role" class="form-select custom-select" required>
                <option value="" style="background: #0d1527;">Pilih Role</option>
                <option value="User" style="background: #0d1527;">User</option>
                <option value="Admin" style="background: #0d1527;">Admin</option>
            </select>
        </div>

        <!--begin::Input group: Password-->
        <div class="fv-row mb-5" data-kt-password-meter="true">
            <div class="custom-input-group">
                <i class="bi bi-key input-icon"></i>
                <input class="form-control" type="password" placeholder="Password" name="password" autocomplete="off"/>
                <span class="password-toggle-btn" data-kt-password-meter-control="visibility">
                    <i class="bi bi-eye-slash fs-5"></i>
                    <i class="bi bi-eye fs-5 d-none"></i>
                </span>
            </div>
        </div>

        <!--begin::Input group: Repeat Password-->
        <div class="fv-row mb-6">
            <div class="custom-input-group">
                <i class="bi bi-shield-check input-icon"></i>
                <input placeholder="Konfirmasi Password" name="password_confirmation" type="password" autocomplete="off" class="form-control"/>
            </div>
        </div>

        <!--begin::Submit button-->
        <div class="d-grid mb-6">
            <button type="submit" id="kt_sign_up_submit" class="btn btn-auth-primary">
                @include('partials/general/_button-indicator', ['label' => 'Daftar Akun'])
            </button>
        </div>

        <!--begin::Sign in link-->
        <div class="text-muted text-center fw-semibold fs-7">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="text-primary fw-bold text-hover-underline ms-1">Sign In</a>
        </div>
    </form>
</x-auth-layout>

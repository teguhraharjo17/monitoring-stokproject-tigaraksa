<x-auth-layout>
    @section('title', 'Login')

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

        .custom-input-group input {
            background-color: rgba(15, 23, 42, 0.7) !important;
            border: 1px solid #1e293b !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
            padding: 12px 14px 12px 42px !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
        }

        .custom-input-group input:focus {
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

        .btn-auth-primary:active {
            transform: translateY(0) !important;
        }

        .auth-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0 20px 0;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #1e293b;
        }

        .auth-divider:not(:empty)::before {
            margin-right: 14px;
        }

        .auth-divider:not(:empty)::after {
            margin-left: 14px;
        }

        .btn-tv-shortcut {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #93c5fd;
            padding: 11px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-tv-shortcut:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #60a5fa;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.2);
        }

        .btn-tv-shortcut .tv-badge {
            background: rgba(37, 99, 235, 0.3);
            border: 1px solid rgba(96, 165, 250, 0.4);
            color: #60a5fa;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 800;
            letter-spacing: 0.5px;
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

        .password-toggle-btn:hover {
            color: #94a3b8;
        }
    </style>

    <!--begin::Form-->
    <form method="POST" action="{{ route('login') }}" class="form w-100" novalidate="novalidate" id="kt_sign_in_form" data-kt-redirect-url="{{ url('/') }}">
        @csrf
        
        <!--begin::Heading-->
        <div class="text-center mb-8">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.25);">
                <i class="bi bi-shield-lock text-primary fs-2"></i>
            </div>
            <h1 class="auth-form-title mb-2">
                Sign In
            </h1>
            <div class="auth-form-subtitle">
                Website Monitoring Stok Project TR
            </div>
        </div>
        <!--end::Heading-->

        <!--begin::Input group: Username-->
        <div class="fv-row mb-5">
            <div class="custom-input-group">
                <i class="bi bi-person input-icon"></i>
                <input type="text" placeholder="Username" name="username" autocomplete="off" class="form-control" autofocus/>
            </div>
        </div>
        <!--end::Input group: Username-->

        <!--begin::Input group: Password-->
        <div class="fv-row mb-6">
            <div class="custom-input-group">
                <i class="bi bi-key input-icon"></i>
                <input type="password" placeholder="Password" name="password" autocomplete="off" class="form-control" />
                <span class="password-toggle-btn" data-kt-password-meter-control="visibility">
                    <i class="bi bi-eye-slash fs-5"></i>
                    <i class="bi bi-eye fs-5 d-none"></i>
                </span>
            </div>
        </div>
        <!--end::Input group: Password-->

        <!--begin::Submit button-->
        <div class="d-grid mb-4">
            <button type="submit" id="kt_sign_in_submit" class="btn btn-auth-primary">
                @include('partials/general/_button-indicator', ['label' => 'Masuk ke Sistem'])
            </button>
        </div>
        <!--end::Submit button-->

        <!--begin::Divider-->
        <div class="auth-divider">
            Akses Cepat
        </div>
        <!--end::Divider-->

        <!--begin::TV Display Link-->
        <div>
            <a href="{{ route('tv.index') }}" target="_blank" class="btn-tv-shortcut">
                <i class="bi bi-tv fs-5"></i>
                <span>Buka Display TV</span>
                <span class="tv-badge">LIVE</span>
            </a>
        </div>
        <!--end::TV Display Link-->

    </form>
    <!--end::Form-->

</x-auth-layout>

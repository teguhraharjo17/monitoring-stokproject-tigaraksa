@extends('layout.master')

@section('content')
<style>
    :root {
        --auth-bg-left: #060913;
        --auth-bg-right: #0c1427;
        --auth-card: #0d1527;
        --auth-border: #1e293b;
        --auth-text: #f8fafc;
        --auth-muted: #94a3b8;
        --auth-accent: #3b82f6;
    }

    body {
        background-color: var(--auth-bg-left) !important;
        color: var(--auth-text) !important;
        font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif !important;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        position: relative;
    }

    /* Left Side: Form Area */
    .auth-form-side {
        background: linear-gradient(180deg, #080d1a 0%, #050811 100%);
        position: relative;
        z-index: 2;
    }

    .auth-form-side::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 1px;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0) 0%, rgba(59, 130, 246, 0.4) 50%, rgba(59, 130, 246, 0) 100%);
        z-index: 10;
    }

    .auth-card {
        background: #0d1527;
        border: 1px solid rgba(59, 130, 246, 0.22);
        border-radius: 20px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 30px rgba(37, 99, 235, 0.08);
        padding: 38px 34px;
        width: 100%;
        max-width: 450px;
        transition: all 0.3s ease;
        position: relative;
    }

    /* Right Side: Showcase Area */
    .auth-showcase-side {
        background: linear-gradient(135deg, #0d1527 0%, #0e1a38 50%, #0a1124 100%);
        border-left: 2px solid #1e293b;
        box-shadow: inset 20px 0 40px rgba(0, 0, 0, 0.5);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 50px 40px;
    }

    .showcase-grid-bg {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(59, 130, 246, 0.15) 1px, transparent 1px);
        background-size: 32px 32px;
        opacity: 0.6;
        pointer-events: none;
    }

    .showcase-ambient-glow {
        position: absolute;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.3) 0%, rgba(6, 182, 212, 0.15) 45%, transparent 70%);
        filter: blur(50px);
        border-radius: 50%;
        z-index: 0;
        pointer-events: none;
        animation: ambientFloat 9s ease-in-out infinite alternate;
    }

    @keyframes ambientFloat {
        0% { transform: translate(-20px, -20px) scale(0.95); opacity: 0.7; }
        100% { transform: translate(20px, 20px) scale(1.15); opacity: 1; }
    }

    .showcase-content {
        position: relative;
        z-index: 1;
        text-align: center;
        max-width: 560px;
    }

    .showcase-img {
        position: relative;
        z-index: 1;
        max-width: 520px;
        width: 100%;
        height: auto;
        display: block;
        filter: drop-shadow(0 20px 35px rgba(0, 0, 0, 0.65));
        animation: floatMockup 6s ease-in-out infinite;
    }

    @keyframes floatMockup {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
    }

    .feature-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(13, 21, 39, 0.85);
        border: 1px solid rgba(59, 130, 246, 0.3);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .feature-pill .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
</style>

<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="d-flex flex-column flex-lg-row flex-column-fluid auth-wrapper">
        
        <!--begin::Form Column (Left Side)-->
        <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-6 p-md-10 justify-content-between align-items-center auth-form-side">
            
            <!--begin::Header Mobile Logo-->
            <div class="d-flex d-lg-none justify-content-center w-100 py-3">
                <img src="{{ asset('assets/media/logos/logo_milenia_login.png') }}" alt="Logo" style="height: 40px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));">
            </div>
            <!--end::Header Mobile Logo-->

            <!--begin::Form Card Wrapper-->
            <div class="d-flex flex-center flex-column flex-lg-row-fluid w-100 my-auto">
                <div class="auth-card">
                    {{ $slot }}
                </div>
            </div>
            <!--end::Form Card Wrapper-->

            <!--begin::Footer-->
            <div class="d-flex flex-center flex-wrap pt-3">
                <div class="d-flex fw-semibold fs-7 text-muted gap-4">
                    <span>&copy; {{ date('Y') }} PT. Milenia Mega Mandiri</span>
                    <span>•</span>
                    <a href="https://ccas.co.id/contact-us/" class="text-hover-primary text-muted" target="_blank">Contact Us</a>
                </div>
            </div>
            <!--end::Footer-->
        </div>
        <!--end::Form Column (Left Side)-->

        <!--begin::Showcase Column (Right Side - Desktop)-->
        <div class="d-none d-lg-flex flex-lg-row-fluid w-lg-50 auth-showcase-side">
            <div class="showcase-grid-bg"></div>
            <div class="showcase-ambient-glow"></div>
            
            <div class="showcase-content">
                <img alt="Logo Milenia" src="{{ asset('assets/media/logos/logo_milenia_login.png') }}" class="mb-4" style="height: 58px; filter: drop-shadow(0 4px 14px rgba(0,0,0,0.6));"/>
                <h2 class="text-white fw-bolder fs-2 mb-2" style="letter-spacing: 0.5px;">Monitoring Stok Project Tigaraksa</h2>
                <p class="text-muted fs-6 mb-5" style="max-width: 460px; margin: 0 auto; line-height: 1.5;">
                    Sistem Kontrol & Monitoring Stok Terintegrasi Realtime untuk Sub Assy, MIP, dan Finish Goods.
                </p>
                
                <div class="d-flex gap-2 justify-content-center flex-wrap mb-6">
                    <span class="feature-pill">
                        <i class="bi bi-tv text-primary fs-7"></i> Live TV Display
                    </span>
                    <span class="feature-pill">
                        <i class="bi bi-gear-wide-connected text-success fs-7"></i> Sub Assy Control
                    </span>
                    <span class="feature-pill">
                        <i class="bi bi-boxes text-warning fs-7"></i> MIP Control
                    </span>
                    <span class="feature-pill">
                        <i class="bi bi-check2-circle text-info fs-7"></i> SPK & FG Tracking
                    </span>
                </div>

                <img class="img-fluid showcase-img mx-auto" src="{{ image('misc/milenia_screen.png') }}" alt="Preview Mockup"/>
            </div>
        </div>
        <!--end::Showcase Column (Right Side)-->

    </div>
</div>
@endsection

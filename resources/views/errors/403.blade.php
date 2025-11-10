<x-default-layout>
    @section('title', '403 - Akses Ditolak')

    {{-- Lottie Animation Loader --}}
    <script 
        src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" 
        type="module">
    </script>

    <div class="d-flex flex-column flex-center text-center px-4 px-md-10 py-10 min-vh-100">
        {{-- Lottie Animation --}}
        <dotlottie-wc 
            src="https://lottie.host/77c60204-043b-45fa-9651-814f2ec59217/9va7R9fyTl.lottie"
            style="width: 100%; max-width: 650px; height: auto;"
            autoplay 
            loop>
        </dotlottie-wc>

        {{-- Error Title --}}
        <h1 class="fw-bolder text-danger mb-3" style="font-size: 5rem;">403</h1>

        {{-- Subtitle --}}
        <h4 class="text-gray-700 fw-semibold mb-3">Akses Ditolak</h4>

        {{-- Description --}}
        <p class="text-gray-600 fs-6 mb-4">
            Anda tidak memiliki izin untuk membuka halaman ini.<br>
            Jika menurut Anda ini kesalahan, silakan hubungi administrator.
        </p>

        {{-- Back to Dashboard --}}
        <a href="{{ route('dashboard.index') }}" class="btn btn-light-primary px-5 py-2 fw-bold shadow-sm">
            ⬅ Kembali ke Dashboard
        </a>
    </div>
</x-default-layout>

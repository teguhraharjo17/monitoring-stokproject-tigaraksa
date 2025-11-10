<!--begin::User account menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
	<!--begin::Menu item-->
	<div class="menu-item px-3">
		<div class="menu-content d-flex align-items-center px-3">
			<!--begin::Avatar-->
			<div class="symbol symbol-50px me-5">
				<img alt="Logo" src="{{ asset('assets/media/avatars/blank.png') }}" />
			</div>
			<!--end::Avatar-->
			<!--begin::Username-->
			<div class="d-flex flex-column" style="max-width: 100%; overflow: hidden;">
				<div class="d-flex align-items-center justify-content-between">
					<div class="fw-bold fs-5 text-gray-900">
						{{ Auth::user()->name }}
					</div>

					<span class="badge badge-light-{{ Auth::user()->role === 'Admin' ? 'danger' : 'primary' }} fw-semibold py-1 px-2 ms-2">
						{{ Auth::user()->role }}
					</span>
				</div>
			</div>
			<!--end::Username-->
		</div>
	</div>
	<!--end::Menu item-->
	<!--begin::Menu separator-->
	<div class="separator my-2"></div>
	<!--end::Menu separator-->
	<!--begin::Menu item-->
	@auth
		@if(Auth::user()->role === 'Admin')
			<div class="menu-item px-5">
				<a href="{{ route('admin.make-account') }}" class="menu-link px-5">
					Make an Account
				</a>
			</div>
		@endif
	@endauth
	<div class="menu-item px-5">
		<a href="{{ route('logout') }}" class="menu-link px-5"
			onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
			Sign Out
		</a>
		<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
			@csrf
		</form>
	</div>
	<!--end::Menu item-->
</div>
<!--end::User account menu-->

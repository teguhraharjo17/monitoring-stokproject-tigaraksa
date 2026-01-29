<!--begin::sidebar menu-->
@php
    $role = auth()->user()->role;
    $isAdmin = $role === 'Admin';
@endphp

<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
	<div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" 
		data-kt-scroll="true"
		data-kt-scroll-activate="true"
		data-kt-scroll-height="auto"
		data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
		data-kt-scroll-wrappers="#kt_app_sidebar_menu"
		data-kt-scroll-offset="5px"
		data-kt-scroll-save-state="true">

		
		<div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">

			<!-- Dashboard -->
			<div class="menu-item pt-5">
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">Dashboard</span>
				</div>
			</div>
			<div class="menu-item">
				<a class="menu-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
					<span class="menu-icon">{!! getIcon('element-11', 'fs-2') !!}</span>
					<span class="menu-title">Dashboard</span>
				</a>
			</div>

			{{-- Master Menu --}}
			@if($isAdmin)
				<div class="menu-item pt-5">
					<div class="menu-content">
						<span class="menu-heading fw-bold text-uppercase fs-7">Master Menu</span>
					</div>
				</div>
				<div class="menu-item">
					<a class="menu-link {{ request()->routeIs('master.user.*') ? 'active' : '' }}" href="{{ route('master.user.index') }}">
						<span class="menu-icon">{!! getIcon('user', 'fs-2') !!}</span>
						<span class="menu-title">Master User</span>
					</a>
				</div>
				<div class="menu-item">
					<a class="menu-link {{ request()->routeIs('master.item.*') ? 'active' : '' }}" href="{{ route('master.item.index') }}">
						<span class="menu-icon">{!! getIcon('delivery-door', 'fs-2') !!}</span>
						<span class="menu-title">Master Item</span>
					</a>
				</div>
				<div class="menu-item">
					<a class="menu-link {{ request()->routeIs('master.company.*') ? 'active' : '' }}" href="{{ route('master.company.index') }}">
						<span class="menu-icon">{!! getIcon('abstract-33', 'fs-2') !!}</span>
						<span class="menu-title">Master Company</span>
					</a>
				</div>
			@endif

			{{-- Data Stock --}}
			@if($isAdmin || in_array($role, ['PPIC']))
			<div class="menu-item pt-5">
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">Data Stock Awal & PO</span>
				</div>
			</div>
			<div class="menu-item">
				<a class="menu-link {{ request()->routeIs('datastock.rekap.*') ? 'active' : '' }}" href="{{ route('datastock.rekap.index') }}">
					<span class="menu-icon">{!! getIcon('notepad', 'fs-2') !!}</span>
					<span class="menu-title">Rekap Data</span>
				</a>
			</div>
			<div class="menu-item">
				<a class="menu-link {{ request()->routeIs('datastock.levelstock.*') ? 'active' : '' }}" href="{{ route('datastock.levelstock.index') }}">
					<span class="menu-icon">{!! getIcon('questionnaire-tablet', 'fs-2') !!}</span>
					<span class="menu-title">Level Stock MIP & FG</span>
				</a>
			</div>
			@endif

			{{-- Monitoring --}}
			<div class="menu-item pt-5">
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">Monitoring Stock</span>
				</div>
			</div>

			@if($isAdmin || in_array($role, ['Sub Assy', 'PPIC']))
				<div class="menu-item">
					<a class="menu-link {{ request()->routeIs('monitoring.subassy.*') ? 'active' : '' }}" href="{{ route('monitoring.subassy.index') }}">
						<span class="menu-icon">{!! getIcon('search-list', 'fs-2') !!}</span>
						<span class="menu-title">Monitoring Sub Assy</span>
					</a>
				</div>
			@endif

			@if($isAdmin || in_array($role, ['MIP', 'PPIC']))
				<div class="menu-item">
					<a class="menu-link {{ request()->routeIs('monitoring.mip.*') ? 'active' : '' }}" href="{{ route('monitoring.mip.index') }}">
						<span class="menu-icon">{!! getIcon('search-list', 'fs-2') !!}</span>
						<span class="menu-title">Monitoring MIP</span>
					</a>
				</div>
			@endif

			@if($isAdmin || in_array($role, ['Finish Good', 'PPIC']))
				<div class="menu-item">
					<a class="menu-link {{ request()->routeIs('monitoring.finishgood.*') ? 'active' : '' }}" href="{{ route('monitoring.finishgood.index') }}">
						<span class="menu-icon">{!! getIcon('search-list', 'fs-2') !!}</span>
						<span class="menu-title">Monitoring Finish Good</span>
					</a>
				</div>
			@endif

			{{-- Report Menu --}}
			<div class="menu-item pt-5">
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">Report Menu</span>
				</div>
			</div>
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('datakanban.*') ? 'here show' : '' }}">
				<span class="menu-link">
					<span class="menu-icon">{!! getIcon('archive', 'fs-2') !!}</span>
					<span class="menu-title">Data Kanban</span>
					<span class="menu-arrow"></span>
				</span>
				<div class="menu-sub menu-sub-accordion">
					<div class="menu-item">
						<a class="menu-link {{ request()->routeIs('datakanban.reguler.*') ? 'active' : '' }}" href="{{ route('datakanban.reguler.index') }}">
							<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
							<span class="menu-title">Reguler</span>
						</a>
					</div>
					<div class="menu-item">
						<a class="menu-link {{ request()->routeIs('datakanban.averageweek.*') ? 'active' : '' }}" href="{{ route('datakanban.averageweek.index') }}">
							<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
							<span class="menu-title">Average Week</span>
						</a>
					</div>
					<div class="menu-item">
						<a class="menu-link {{ request()->routeIs('datakanban.osperweek.*') ? 'active' : '' }}" href="{{ route('datakanban.osperweek.index') }}">
							<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
							<span class="menu-title">O/S per Weekly</span>
						</a>
					</div>
				</div>
			</div>

			{{-- SPK Packing Member --}}
			@if($isAdmin || in_array($role, ['Packing', 'PPIC', 'MIP', 'Finish Good', 'Diketahui']))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('spkpacking.*') ? 'here show' : '' }}">
					<span class="menu-link">
						<span class="menu-icon">{!! getIcon('scroll', 'fs-2') !!}</span>
						<span class="menu-title">SPK Packing Member</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion">
						@if($isAdmin || in_array($role, ['Finish Good', 'PPIC']))
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('spkpacking.formspk.*') ? 'active' : '' }}" href="{{ route('spkpacking.formspk.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">Form SPK</span>
								</a>
							</div>
						@endif
						@if($isAdmin || $role === 'Packing')
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('spkpacking.approvepacking.*') ? 'active' : '' }}" href="{{ route('spkpacking.approvepacking.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">Approve Packing Member</span>
								</a>
							</div>
						@endif

						@if($isAdmin || $role === 'PPIC')
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('spkpacking.approveppic.*') ? 'active' : '' }}" href="{{ route('spkpacking.approveppic.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">Approve PPIC</span>
								</a>
							</div>
						@endif

						@if($isAdmin || $role === 'MIP')
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('spkpacking.approvemip.*') ? 'active' : '' }}" href="{{ route('spkpacking.approvemip.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">Approve MIP</span>
								</a>
							</div>
						@endif

						@if($isAdmin || $role === 'Finish Good')
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('spkpacking.approvefg.*') ? 'active' : '' }}" href="{{ route('spkpacking.approvefg.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">Approve Finish Good</span>
								</a>
							</div>
						@endif

						@if($isAdmin || $role === 'Diketahui')
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('spkpacking.approvediketahui.*') ? 'active' : '' }}" href="{{ route('spkpacking.approvediketahui.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">Approve Diketahui</span>
								</a>
							</div>
						@endif

						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('spkpacking.spklist.*') ? 'active' : '' }}" href="{{ route('spkpacking.spklist.index') }}">
								<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
								<span class="menu-title">SPK List</span>
							</a>
						</div>
					</div>
				</div>
			@endif

			{{-- Optional Report --}}
			<div class="menu-item">
				<a class="menu-link" href="#">
					<span class="menu-icon">{!! getIcon('book-square', 'fs-2') !!}</span>
					<span class="menu-title">Report Proses Packing MIP</span>
				</a>
			</div>

		</div>
	</div>
</div>
<!--end::sidebar menu-->

@extends('layouts.master')

@push('styles')
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/orders/css/orders.css?v=' . filemtime(public_path() . '/modules/orders/css/orders.css')) }}" />
@endpush

@push('scripts')
<script src="{{ asset('modules/orders/js/orders.js?v=' . filemtime(public_path() . '/modules/orders/js/orders.js')) }}"></script>
<script>
$(document).ready(function() { 
	$('.filter-submit').on('change', function(e){
		$(this).closest('form').submit();
	});
});
</script>
@endpush

@php
app('pathway')
	->append(
		trans('orders::orders.orders'),
		route('site.orders.index')
	)
	->append(
		trans('orders::orders.recurring'),
		route('site.orders.recurring')
	)
	->append(
		'#' . $item->id,
		route('site.orders.recurring.read', ['id' => $item->id])
	);
@endphp

@section('content')
@component('orders::site.submenu')
	recur
@endcomponent
<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	<h2 class="sr-only">{{ trans('orders::orders.recurring') }}</h2>

	<form action="{{ route('site.orders.recurring') }}" method="get" class="row">
		<div class="sidenav col-lg-3 col-md-3 col-sm-12 col-xs-12">
			<fieldset class="filters mt-0">
				<legend class="sr-only">Filter</legend>

				<div class="form-group">
					<label for="filter_search">{{ trans('search.label') }}</label>
					<input type="text" name="search" id="filter_search" class="form-control filter" placeholder="{{ trans('search.placeholder') }}" value="" />
				</div>
			</fieldset>
		</div>
		<div class="contentInner col-lg-9 col-md-9 col-sm-12 col-xs-12">
			
			<div class="card">
				<div class="card-header">
					<div class="row">
						<div class="col-md-8">
							<h3 class="card-title">Recurring Item #{{ $item->id }}</h3>
						</div>
						<div class="col-md-4 text-right">
							@if ($item->datepaiduntil == $item->datebilleduntil && $item->datepaiduntil && $item->datepaiduntil != '0000-00-00 00:00:00')
								<button class="btn btn-sm btn-secondary recur-renew tip" title="Generate an order to extend service for this recurring item" data-item="{{ $item->id }}">Renew Now</button>
							@endif
						</div>
					</div>
				</div>
				<div class="card-body">
					<div class="form-group">
						<p><strong>{{ trans('orders::orders.product') }}:</strong></p>
						<p class="form-text">{{ $item->product->name }}</p>
					</div>
					<div class="form-group">
						<p><strong>{{ trans('orders::orders.group') }}:</strong></p>
						<p class="form-text">
							@foreach ($item->ordergroups as $group)
								{{ $group }}<br />
							@endforeach
						</p>
					</div>
			
					@if ($item->start())
					<div class="row">
						<div class="col">
							<p><strong>{{ trans('orders::orders.started') }}:</strong></p>
							<p>
								{{ $item->start()->format('F j, Y') }}
							</p>
						</div>
						<div class="col">
							<p><strong>{{ trans('orders::orders.paid through') }}:</strong></p>
							<p>
								{{ $item->paiduntil->format('F j, Y') }}
							</p>
						</div>
						@if ($item->paiduntil != $item->billeduntil)
							<div class="col">
								<p><strong>{{ trans('orders::orders.billed through') }}:</strong></p>
								<p>
									{{ $item->billeduntil->format('F j, Y') }}
								</p>
							</div>
						@endif
					</div>
					@endif
				</div>
			</div>

			@if (count($items))
				<table class="table table-hover">
					<caption>{{ trans('orders::orders.order history') }}</caption>
					<thead>
						<tr>
							<th scope="col" class="priority-5">
								{{ trans('orders::orders.order') }}
							</th>
							<th scope="col" class="priority-4">
								{{ trans('orders::orders.status') }}
							</th>
							<th scope="col">
								{{ trans('orders::orders.quantity') }}
							</th>
							<th scope="col" class="priority-4" colspan="2">
								{{ trans('orders::orders.service') }}
							</th>
							<th scope="col" class="priority-4 text-right">
								{{ trans('orders::orders.price') }}
							</th>
						</tr>
					</thead>
					<tbody>
					@php
						$total = 0;
					@endphp
					@foreach ($items as $i => $row)
						<tr>
							<td class="priority-5">
								{{ $row->orderid }}
							</td>
							<td>
								@if ($row->isFulfilled())
									Paid
								@elseif ($row->order->isCanceled())
									Canceled
								@else
									Billed
								@endif
							</td>
							<td>
								{{ $row->quantity }}
							</td>
							<td class="priority-4">
								@if ($row->order->isCanceled())
									-
								@else
									{{ $row->start->format('Y-m-d') }}
								@endif
							</td>
							<td class="priority-4">
								@if ($row->order->isCanceled())
									-
								@else
									{{ $row->end->format('Y-m-d') }}
								@endif
							</td>
							<td class="text-right">
								@if ($row->order->isCanceled())
									-
								@else
									$&nbsp;{{ $item->formatCurrency($row->price) }}
								@endif
								@php
									$total += $row->price;
								@endphp
							</td>
						</tr>
					@endforeach
					</tbody>
					<tfoot>
						<tr>
							<th scope="row" colspan="5" class="text-right">
								<strong>Total</strong>
							</th>
							<td class="text-right">
								$&nbsp;{{ $item->formatCurrency($total) }}
							</td>
						</tr>
					</tfoot>
				</table>
			@else
				<p class="alert alert-info">No orders found.</p>
			@endif
		</div>
		@csrf
	</form>
</div>
@stop
<?php
$path = ltrim($file->getRelativePath(), '/');
$icon = asset('modules/media/filetypes/folder.svg');
$ext = 'folder';
?>
<div class="media-item media-item-thumb">
	<div class="media-preview">
		<div class="media-preview-inner">
			<a class="media-thumb folder-item" data-folder="/{{ $path }}" data-href="{{ route('admin.media.medialist', ['folder' => '/' . $path]) }}" href="{{ route('admin.media.index', ['folder' => '/' . $path]) }}">
				<span class="media-preview-shim"></span><!--
				--><img src="{{ $icon }}" alt="{{ $file->getFilename() }}" width="80" />
			</a>
			<span class="media-options-btn"></span>
		</div>
	</div>
	<div class="media-info">
		<div class="media-name">
			{{ $file->getShortName() }}
		</div>
		<div class="media-options">
			<ul>
				<li>
					<a class="icon-info media-opt-info" href="#fileinfo-{{ $file->getId() }}">{{ trans('media::media.file info') }}</a>
				</li>
			@if (auth()->user()->can('edit media'))
				<li>
					<a class="icon-edit media-opt-rename" href="{{ route('admin.media.medialist', ['folder' => '/' . $path]) }}" data-api="{{ route('api.media.rename') }}" data-path="{{ dirname($path) }}" data-name="{{ basename($path) }}" data-prompt="New name">{{ trans('media::media.rename') }}</a>
				</li>
			@endif
			@if (auth()->user()->can('delete media'))
				<li>
					<span class="separator"></span>
				</li>
				<li>
					<a class="icon-trash media-opt-delete" href="{{ route('admin.media.delete', ['folder' => $path]) }}" data-api="{{ route('api.media.delete', ['items[0][path]' => $path, 'items[0][type]' => 'dir']) }}">{{ trans('global.button.delete') }}</a>
				</li>
			@endif
			</ul>
		</div>

		<div class="dialog dialog-filerename" id="filerename-{{ $file->getId() }}" title="{{ trans('media::media.rename') }}">
			<div class="form-group">
				<label for="rename-{{ $file->getId() }}" class="sr-only">{{ trans('pages::pages.path') }}:</label>
				<div class="input-group mb-2 mr-sm-2">
					<div class="input-group-prepend">
						<div class="input-group-text">/{{ (strstr($path, '/') ? dirname($path) . '/' : '') }}</div>
					</div>
					<input type="text" name="rename" id="rename-{{ $file->getId() }}" class="form-control" maxlength="250" value="" />
				</div>
			</div>
		</div>

		@include('media::medialist.info')
		@include('media::medialist.path')
	</div>
</div>

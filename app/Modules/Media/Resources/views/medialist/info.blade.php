<div class="dialog dialog-fileinfo" id="fileinfo-{{ $file->getId() }}" title="{{ trans('media::media.file info') }}">
	<div class="row">
		<div class="col col-md-4">
			<div class="media-preview">
				<div class="media-preview-inner">
					@if ($file->isImage())
						<div class="media-thumb img-preview {{ $ext }}" title="{{ $file->getFilename() }}">
							<span class="media-preview-shim"></span><!--
							--><img src="{{ $file->getUrl() }}" alt="{{ trans('media::media.image title', ['name' => $file->getFilename(), 'size' => $file->getFormattedSize()]) }}" width="<?php echo ($file->getWidth() < 260) ? $file->getWidth() : '260'; ?>" />
						</div>
					@else
						<div class="media-thumb doc-item {{ $ext }}" title="{{ $file->getFilename() }}">
							<span class="media-preview-shim"></span><!--
							--><img src="{{ asset($icon) }}" alt="{{ trans('media::media.image title', ['name' => $file->getFilename(), 'size' => $file->getFormattedSize()]) }}" width="80" />
						</div>
					@endif
				</div>
			</div>
		</div>
		<div class="col col-md-8">
			<div class="form-group">
				<span class="media-info-label">{{ trans('media::media.list.name') }}:</span>
				<span class="media-info-value">{{ $file->getFilename() }}</span>
			</div>

			<div class="form-group">
				<span class="media-info-label">{{ trans('media::media.list.path') }}:</span>
				<span class="media-info-value">{{ $file->getRelativePath() }}</span>
			</div>

			@if (!$file->isDir())
				@if ($file->isImage())
					<div class="row">
						<div class="col col-md-4">
				@endif
				<div class="form-group">
					<span class="media-info-label">{{ trans('media::media.list.size') }}:</span>
					<span class="media-info-value">{{ $file->getFormattedSize() }}</span>
				</div>
				@if ($file->isImage())
						</div>
						<div class="col col-md-4">
							<div class="form-group">
								<span class="media-info-label">{{ trans('media::media.list.width') }}:</span>
								<span class="media-info-value">{{ $file->getWidth() }} px</span>
							</div>
						</div>
						<div class="col col-md-4">
							<div class="form-group">
								<span class="media-info-label">{{ trans('media::media.list.height') }}:</span>
								<span class="media-info-value">{{ $file->getHeight() }} px</span>
							</div>
						</div>
					</div>
				@endif
			@endif

			<div class="form-group">
				<span class="media-info-label">{{ trans('media::media.list.modified') }}:</span>
				<span class="media-info-value">{{ $file->getLastModified()->format('Y-m-d H:i:s') }}</span>
			</div>
		</div>
	</div>
</div>

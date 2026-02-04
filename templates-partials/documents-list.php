<?php
/**
 * Documents list partial.
 *
 * @package OrgManagement
 */

if ( ! isset( $org_id ) ) {
	$org_id = '';
}

$documents = $documents ?? [];
$category  = $category ?? '';
$notice    = $notice ?? null;
?>
<div class="documents-list">
	<?php if ( $notice ) : ?>
		<div class="notifications-wt:inline-container">
			<div class="notification notification-<?php echo esc_attr( $notice['type'] ); ?> notification-wt:inline">
				<div class="notification-icon">
					<?php
					$icon = match( $notice['type'] ) {
						'success' => '✓',
						'error' => '✕',
						'warning' => '!',
						default => 'i'
					};
					echo esc_html( $icon );
					?>
				</div>
				<div class="notification-content">
					<div class="notification-message"><?php echo wp_kses_post( $notice['message'] ); ?></div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="documents-toolbar wt:mb-4">
		<form class="document-upload-form"
		      ds-post="<?php echo esc_url( rest_url( 'org-management/v1/documents/upload' ) ); ?>"
		      ds-target="#documents-list-container"
		      ds-swap="innerHTML"
		      enctype="multipart/form-data">
			<input type="hidden" name="org_id" value="<?php echo esc_attr( $org_id ); ?>">
			<input type="hidden" name="category" value="<?php echo esc_attr( $category ); ?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'org_management_document_upload_' . $org_id ) ); ?>">

			<div class="grid wt:grid-cols-1 md:wt:grid-cols-12 wt:gap-4 wt:items-end">
				<div class="md:col-span-5">
					<label for="document_file" class="wt:block wt:text-sm wt:font-medium wt:text-gray-700 wt:mb-1">
						<?php esc_html_e( 'Select Document', 'wicket-acc' ); ?>
					</label>
					<input type="file"
					       id="document_file"
					       name="document"
					       class="wt:block wt:w-full wt:text-sm wt:text-gray-500
					              file:mr-4 file:py-2 file:px-4
					              file:rounded file:border-0
					              file:text-sm file:font-semibold
					              file:bg-blue-50 file:text-blue-700
					              hover:file:bg-blue-100"
					       required>
				</div>

				<div class="md:col-span-4">
					<label for="document_title" class="wt:block wt:text-sm wt:font-medium wt:text-gray-700 wt:mb-1">
						<?php esc_html_e( 'Document Title', 'wicket-acc' ); ?>
					</label>
					<input type="text"
					       id="document_title"
					       name="title"
					       class="wt:w-full wt:px-3 wt:py-2 wt:border wt:border-gray-300 wt:rounded-md wt:shadow-xs focus:wt:outline-hidden focus:wt:ring-2 focus:wt:ring-blue-500 wt:focus:border-blue-500 sm:wt:text-sm"
					       placeholder="<?php esc_attr_e( 'Enter document title', 'wicket-acc' ); ?>">
				</div>

				<div class="md:col-span-2">
					<button type="submit" class="wt:w-full wt:flex wt:justify-center wt:py-2 wt:px-4 wt:border wt:border-transparent wt:rounded-md wt:shadow-xs wt:text-sm wt:font-medium wt:text-white wt:bg-blue-600 wt:hover:bg-blue-700 focus:wt:outline-hidden focus:wt:ring-2 focus:wt:ring-offset-2 wt:focus:ring-blue-500">
						<?php esc_html_e( 'Upload Document', 'wicket-acc' ); ?>
					</button>
				</div>
			</div>

			<div class="wt:mt-2">
				<label for="document_description" class="wt:block wt:text-sm wt:font-medium wt:text-gray-700 wt:mb-1">
					<?php esc_html_e( 'Description (Optional)', 'wicket-acc' ); ?>
				</label>
				<textarea id="document_description"
				          name="description"
				          rows="2"
				          class="wt:shadow-xs focus:wt:ring-2 focus:wt:ring-blue-500 wt:focus:border-blue-500 wt:mt-1 wt:block wt:w-full sm:wt:text-sm wt:border wt:border-gray-300 wt:rounded-md wt:p-2"
				          placeholder="<?php esc_attr_e( 'Enter document description', 'wicket-acc' ); ?>"></textarea>
			</div>
		</form>
	</div>

	<?php if ( ! empty( $documents ) ) : ?>
		<div class="documents-wt:grid wt:mt-6">
			<?php foreach ( $documents as $document ) : ?>
				<div class="document-card wt:border wt:border-gray-200 wt:rounded-md wt:p-4 wt:mb-3 wt:flex wt:items-center wt:justify-between" data-document-id="<?php echo esc_attr( $document['id'] ); ?>">
					<div class="document-info wt:flex wt:items-center">
						<div class="document-icon wt:mr-3">
							<?php
							// Show appropriate icon based on file type
							$file_ext = pathinfo( $document['filename'], PATHINFO_EXTENSION );
							$file_ext_lower = strtolower( $file_ext );
							$icon_class = 'document-file-icon';

							switch ( $file_ext_lower ) {
								case 'pdf':
									$icon_class = 'document-pdf-icon';
									break;
								case 'doc':
								case 'docx':
									$icon_class = 'document-word-icon';
									break;
								case 'xls':
								case 'xlsx':
									$icon_class = 'document-excel-icon';
									break;
								case 'jpg':
								case 'jpeg':
								case 'png':
								case 'gif':
									$icon_class = 'document-image-icon';
									break;
								default:
									$icon_class = 'document-generic-icon';
							}
							?>
							<span class="<?php echo esc_attr( $icon_class ); ?>" style="font-size: 24px;">📄</span>
						</div>
						<div class="document-details">
							<h3 class="wt:font-medium wt:text-gray-900"><?php echo esc_html( $document['title'] ); ?></h3>
							<p class="wt:text-sm wt:text-gray-500"><?php echo esc_html( $document['filename'] ); ?> (<?php echo esc_html( size_format( $document['filesize'] ) ); ?>)</p>
							<?php if ( ! empty( $document['description'] ) ) : ?>
								<p class="wt:text-sm wt:text-gray-600 wt:mt-1"><?php echo esc_html( $document['description'] ); ?></p>
							<?php endif; ?>
							<p class="wt:text-xs wt:text-gray-400 wt:mt-1">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $document['upload_date'] ) ) ); ?>
								<?php if ( ! empty( $document['category'] ) ) : ?>
									<span class="wt:ml-2 wt:px-2 wt:py-0.5 wt:bg-gray-100 wt:rounded-full wt:text-xs"><?php echo esc_html( $document['category'] ); ?></span>
								<?php endif; ?>
							</p>
						</div>
					</div>
					<div class="document-actions wt:flex wt:gap-2">
						<a href="<?php echo esc_url( $document['url'] ); ?>"
						   target="_blank"
						   class="wt:text-blue-600 wt:hover:text-blue-900 wt:text-sm wt:font-medium"
						   download>
							<?php esc_html_e( 'Download', 'wicket-acc' ); ?>
						</a>
						|
						<button
							ds-delete="<?php echo esc_url( rest_url( 'org-management/v1/documents/delete/' . $document['id'] . '?org_id=' . $org_id . '&category=' . $category ) ); ?>"
							ds-target="#documents-list-container"
							ds-swap="innerHTML"
							ds-confirm="<?php esc_attr_e( 'Are you sure you want to delete this document?', 'wicket-acc' ); ?>"
							class="wt:text-red-600 wt:hover:text-red-900 wt:text-sm wt:font-medium">
							<?php esc_html_e( 'Delete', 'wicket-acc' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="empty-documents-state wt:text-center wt:py-8">
			<div class="wt:text-gray-400 wt:text-5xl wt:mb-4">📁</div>
			<h3 class="wt:text-lg wt:font-medium wt:text-gray-900 wt:mb-1"><?php esc_html_e( 'No documents found', 'wicket-acc' ); ?></h3>
			<p class="wt:text-gray-500"><?php esc_html_e( 'Upload your first document using the form above.', 'wicket-acc' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<?php
/**
 * Template Partial for displaying subsidiary search results.
 *
 * @package OrgManagement
 */

// This file is designed to be loaded via the template loader in `template-helper.php`.
// It generates a Datastar SSE event to patch the search results.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Manually load dependencies and services as we are outside the main plugin flow.
require_once dirname( __DIR__ ) . '/src/services/ConfigService.php';
require_once dirname( __DIR__ ) . '/src/services/SubsidiaryService.php';


use OrgManagement\Services\ConfigService;
use OrgManagement\Services\SubsidiaryService;
use Datastar\ServerSentEventGenerator;
use Datastar\Events\PatchElements;

// Instantiate services
$config_service = new ConfigService();
$subsidiary_service = new SubsidiaryService( $config_service );

// Get parameters from the request
$search_term = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
$org_id = isset( $_REQUEST['org_id'] ) ? sanitize_text_field( $_REQUEST['org_id'] ) : '';

// Perform the search
$candidates = [];
if ( ! empty( $search_term ) && ! empty( $org_id ) ) {
    $candidates = $subsidiary_service->search_subsidiary_candidates( $search_term, $org_id );
}

// Generate the HTML for the search results
ob_start();
?>
<div id="subsidiary-search-results" class="search-results-dropdown">
    <?php if ( ! empty( $candidates ) ) : ?>
        <div class="search-results">
            <?php foreach ( $candidates as $candidate ) : ?>
                <div class="search-result-item">
                    <div class="candidate-info">
                        <strong><?php echo esc_html( $candidate['name'] ); ?></strong>
                        <span class="candidate-type"><?php echo esc_html( $candidate['type'] ); ?></span>
                    </div>
                    <button class="button button--small button--primary"
                            onclick="addSubsidiary('<?php echo esc_js( $candidate['id'] ); ?>', '<?php echo esc_js( $candidate['name'] ); ?>')">
                        <?php esc_html_e( 'Add', 'wicket-acc' ); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ( ! empty( $search_term ) ) : ?>
        <div class="no-results"><?php esc_html_e( 'No organizations found.', 'wicket-acc' ); ?></div>
    <?php endif; ?>
</div>
<?php
$html_content = ob_get_clean();

// Create a PatchElements event
$patch_event = new PatchElements( '#subsidiary-search-results', $html_content );

// Create a ServerSentEventGenerator and add the event
$sse_generator = new ServerSentEventGenerator();
$sse_generator->addEvent( $patch_event );

// Send the SSE event
// header( 'Content-Type: text/event-stream' );
// header( 'Cache-Control: no-cache' );

echo $sse_generator->render();
flush();

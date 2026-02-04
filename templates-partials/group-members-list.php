<?php
/**
 * Group members list partial.
 *
 * @package OrgManagement
 */

use OrgManagement\Services\GroupService;
use OrgManagement\Services\MembershipService;
use OrgManagement\Services\AdditionalSeatsService;
use OrgManagement\Services\ConfigService;
use OrgManagement\Helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$group_uuid = isset( $_GET['group_uuid'] ) ? sanitize_text_field( (string) $_GET['group_uuid'] ) : '';
$org_uuid = isset( $_GET['org_uuid'] ) ? sanitize_text_field( (string) $_GET['org_uuid'] ) : '';

$members = isset( $group_members ) && is_array( $group_members ) ? $group_members : [];
$pagination = isset( $group_pagination ) && is_array( $group_pagination ) ? $group_pagination : [];
$query = isset( $group_query ) ? (string) $group_query : '';

$members_list_endpoint = isset( $group_members_list_endpoint ) ? (string) $group_members_list_endpoint : \OrgManagement\Helpers\TemplateHelper::template_url() . 'group-members-list';
$members_list_target = isset( $group_members_list_target ) ? (string) $group_members_list_target : 'group-members-list-container-' . sanitize_html_class( $group_uuid ?: 'default' );

$page = (int) ( $pagination['currentPage'] ?? 1 );
$total_pages = (int) ( $pagination['totalPages'] ?? 1 );
$page_size = (int) ( $pagination['pageSize'] ?? 15 );
$total_items = (int) ( $pagination['totalItems'] ?? count( $members ) );

$page = max( 1, $page );
$total_pages = max( 1, $total_pages );

$membership_service = new MembershipService();
$config_service = new ConfigService();
$additional_seats_service = new AdditionalSeatsService( $config_service );
$member_service = new \OrgManagement\Services\MemberService( $config_service );

$membership_uuid = '';
if ( ! empty( $org_uuid ) ) {
    $membership_uuid = $membership_service->getMembershipForOrganization( $org_uuid );
}

$has_seats_available = true;
$max_seats = null;
$active_seats = 0;
$purchase_seats_url = '';
$can_purchase_seats = false;
if ( $membership_uuid ) {
    $membership_data = $membership_service->getOrgMembershipData( $membership_uuid );
    if ( $membership_data && isset( $membership_data['data']['attributes'] ) ) {
        $max_seats = $membership_data['data']['attributes']['max_assignments'] ?? null;
        $active_seats = (int) ( $membership_data['data']['attributes']['active_assignments_count'] ?? 0 );
        if ( $max_seats !== null && $active_seats >= (int) $max_seats ) {
            $has_seats_available = false;
        }
    }

    if ( ! $has_seats_available ) {
        $can_purchase_seats = $additional_seats_service->can_purchase_additional_seats( $org_uuid );
        if ( $can_purchase_seats ) {
            $purchase_seats_url = $additional_seats_service->get_purchase_form_url( $org_uuid, $membership_uuid );
        }
    }
}

$base_query_args = [
    'group_uuid' => $group_uuid,
    'org_uuid' => $org_uuid,
    'query' => $query,
    'size' => $page_size,
];

$build_url = static function ( int $page_number ) use ( $members_list_endpoint, $base_query_args ) {
    $args = array_merge( $base_query_args, [ 'page' => $page_number ] );
    $separator = str_contains( $members_list_endpoint, '?' ) ? '&' : '?';
    $query_args = http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
    return $members_list_endpoint . $separator . $query_args;
};

$build_action = static function ( int $page_number ) use ( $build_url ) {
    return "@get('" . $build_url( $page_number ) . "')";
};

$remove_member_endpoint = \OrgManagement\Helpers\TemplateHelper::template_url() . 'process/remove-group-member';
$refresh_action = "@get('" . $build_url( 1 ) . "') >> select('#" . $members_list_target . "') | set(html)";

?>
<div
    id="<?php echo esc_attr( $members_list_target ); ?>"
    class="wt:mt-6 wt:flex wt:flex-col wt:gap-4 wt:relative"
    data-page="<?php echo esc_attr( (string) $page ); ?>"
    data-attr:aria-busy="$membersLoading">
    <div id="group-member-messages" class="wt:mb-3"></div>
    <div class="wt:text-xl wt:font-semibold wt:mb-3">
        <?php if ( $max_seats !== null ) : ?>
            <?php printf( esc_html__( 'Seats assigned: %1$d / %2$d', 'wicket-acc' ), (int) $active_seats, (int) $max_seats ); ?>
        <?php else : ?>
            <?php esc_html_e( 'Number of assigned people:', 'wicket-acc' ); ?>
            <?php echo (int) $total_items; ?>
        <?php endif; ?>
    </div>

    <?php if ( ! $has_seats_available ) : ?>
        <div class="wt:rounded-md wt:bg-[var(--om-bg-light-neutral)] wt:p-4">
            <p class="wt:text-sm wt:text-[var(--om-text-content)]">
                <?php esc_html_e( 'No seats available. Please purchase additional seats to add more members.', 'wicket-acc' ); ?>
            </p>
            <?php if ( $can_purchase_seats && $purchase_seats_url ) : ?>
                <div class="wt:mt-3">
                    <a class="button button--primary additional-seats-cta" href="<?php echo esc_url( $purchase_seats_url ); ?>">
                        <?php esc_html_e( 'Purchase Additional Seats', 'wicket-acc' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( empty( $members ) ) : ?>
        <p class="wt:text-gray-500 wt:p-4"><?php esc_html_e( 'No members found.', 'wicket-acc' ); ?></p>
    <?php else : ?>
        <?php foreach ( $members as $member ) :
            $member_uuid = $member['person_uuid'] ?? '';
            $member_name = $member['full_name'] ?? '';
            $member_email = $member['email'] ?? '';
            $member_role_label = $member['role'] ?? '';
            $group_member_id = $member['group_member_id'] ?? '';
            $is_confirmed = $member_uuid ? $member_service->isUserConfirmed( $member_uuid ) : false;
        ?>
            <div class="member-card wt:bg-[var(--om-bg-light-neutral)] wt:rounded-[var(--om-card-corner-radius)] wt:p-6 wt:transition:opacity wt:duration-300">
                <div class="wt:flex wt:w-full md:wt:flex-row wt:items-start wt:justify-between wt:gap-4">
                    <div class="wt:flex wt:flex-col wt:gap-2 wt:w-full md:wt:w-4/5">
                        <div class="wt:flex wt:flex-col sm:wt:flex-row wt:items-start sm:wt:items-center wt:gap-2">
                            <div class="wt:flex wt:items-center wt:gap-2">
                                <h3 class="wt:text-xl wt:font-medium wt:text-[var(--om-text-content)] wt:mb-0">
                                    <?php echo esc_html( $member_name ); ?>
                                </h3>
                                <?php if ( $is_confirmed ) : ?>
                                    <span class="wt:text-[var(--om-text-content)]" title="<?php esc_attr_e( 'Account confirmed', 'wicket-acc' ); ?>">
                                        <span class="wt:inline-block wt:w-2 wt:h-2 wt:rounded-full wt:bg-green-500" aria-hidden="true"></span>
                                    </span>
                                <?php else : ?>
                                    <span class="wt:text-[var(--om-text-content)]" title="<?php esc_attr_e( 'Account not confirmed', 'wicket-acc' ); ?>">
                                        <span class="wt:inline-block wt:w-2 wt:h-2 wt:rounded-full wt:bg-gray-400" aria-hidden="true"></span>
                                    </span>
                                    <span class="wt:text-[var(--om-text-warning)] wt:whitespace-nowrap" title="<?php esc_attr_e( 'Account not confirmed', 'wicket-acc' ); ?>">
                                        <?php esc_html_e( 'Account not confirmed', 'wicket-acc' ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ( $member_email ) : ?>
                            <div class="wt:flex wt:items-center wt:gap-2">
                                <a href="mailto:<?php echo esc_attr( $member_email ); ?>" class="wt:text-sm wt:text-[var(--om-text-interactive)] wt:hover:underline">
                                    <?php echo esc_html( $member_email ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ( $member_role_label ) : ?>
                            <div class="wt:flex wt:items-baseline wt:gap-2 wt:text-sm">
                                <strong><?php esc_html_e( 'Role:', 'wicket-acc' ); ?></strong>
                                <span class="wt:text-[var(--om-text-content)]">
                                    <?php echo esc_html( ucwords( str_replace( '_', ' ', $member_role_label ) ) ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="wt:flex wt:flex-col sm:wt:flex-row wt:items-stretch sm:wt:items-start wt:gap-2 wt:justify-between md:wt:auto wt:shrink-0">
                        <button type="button" class="acc-remove-button remove-member-button button button--secondary wt:inline-flex wt:items-center wt:justify-between wt:gap-2 wt:px-4 wt:py-2 wt:bg-[var(--om-bg-light-neutral)] wt:text-sm wt:border wt:border-[var(--om-bg-interactive)] wt:transition-colors wt:whitespace-nowrap"
                            data-on:click="
                                $currentRemoveMemberUuid = '<?php echo esc_js( $member_uuid ); ?>';
                                $currentRemoveMemberName = '<?php echo esc_js( $member_name ); ?>';
                                $currentRemoveMemberEmail = '<?php echo esc_js( $member_email ); ?>';
                                $currentRemoveMemberGroupMemberId = '<?php echo esc_js( $group_member_id ); ?>';
                                $currentRemoveMemberRole = '<?php echo esc_js( $member_role_label ); ?>';
                                $removeMemberModalOpen = true
                            ">
                            <?php esc_html_e( 'Remove', 'wicket-acc' ); ?>
                            <svg class="wt:w-4 wt:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <nav class="members-pagination wt:mt-6 wt:flex wt:flex-col wt:gap-4" aria-label="<?php esc_attr_e( 'Group members pagination', 'wicket-acc' ); ?>">
        <div class="members-pagination__info wt:w-full wt:text-left wt:text-sm wt:text-[var(--om-text-content)]">
            <?php
            if ( $total_items > 0 ) {
                $first = ( ( $page - 1 ) * $page_size ) + 1;
                $last = min( $total_items, $page * $page_size );
                echo esc_html( sprintf( __( 'Showing %1$d–%2$d of %3$d', 'wicket-acc' ), $first, $last, $total_items ) );
            } else {
                esc_html_e( 'No members to display.', 'wicket-acc' );
            }
            ?>
        </div>
        <div class="members-pagination__controls wt:w-full wt:flex wt:items-center wt:gap-2 wt:justify-end wt:self-end">
            <?php $prev_disabled = $page <= 1; ?>
            <button type="button"
                class="members-pagination__btn members-pagination__btn--prev button button--secondary wt:px-3 wt:py-2 wt:text-sm"
                <?php if ( $prev_disabled ) : ?>disabled<?php endif; ?>
                <?php if ( ! $prev_disabled ) : ?>data-on:click="<?php echo esc_attr( $build_action( $page - 1 ) ); ?>" <?php endif; ?>
                data-on:success="<?php echo esc_attr( wp_sprintf( "select('#%s') | set(html)", $members_list_target ) ); ?>"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading">
                <?php esc_html_e( 'Previous', 'wicket-acc' ); ?>
            </button>
            <div class="members-pagination__pages wt:flex wt:items-center wt:gap-1">
                <?php for ( $i = 1; $i <= $total_pages; $i++ ) :
                    $is_current = ( $i === $page );
                ?>
                    <button type="button"
                        class="members-pagination__btn members-pagination__btn--page button wt:px-3 wt:py-2 wt:text-sm <?php echo $is_current ? 'button--primary' : 'button--secondary'; ?>"
                        <?php if ( $is_current ) : ?>disabled<?php endif; ?>
                        <?php if ( ! $is_current ) : ?>data-on:click="<?php echo esc_attr( $build_action( $i ) ); ?>" <?php endif; ?>
                        data-on:success="<?php echo esc_attr( wp_sprintf( "select('#%s') | set(html)", $members_list_target ) ); ?>"
                        data-indicator:members-loading
                        data-attr:disabled="$membersLoading">
                        <?php echo esc_html( (string) $i ); ?>
                    </button>
                <?php endfor; ?>
            </div>
            <?php $next_disabled = $page >= $total_pages; ?>
            <button type="button"
                class="members-pagination__btn members-pagination__btn--next button button--secondary wt:px-3 wt:py-2 wt:text-sm"
                <?php if ( $next_disabled ) : ?>disabled<?php endif; ?>
                <?php if ( ! $next_disabled ) : ?>data-on:click="<?php echo esc_attr( $build_action( $page + 1 ) ); ?>" <?php endif; ?>
                data-on:success="<?php echo esc_attr( wp_sprintf( "select('#%s') | set(html)", $members_list_target ) ); ?>"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading">
                <?php esc_html_e( 'Next', 'wicket-acc' ); ?>
            </button>
        </div>
    </nav>

    <div class="wt:mt-6">
        <?php if ( $has_seats_available ) : ?>
            <button type="button"
                class="button button--primary add-member-button wt:w-full wt:py-2"
                data-on:click="$addMemberModalOpen = true">
                <?php esc_html_e( 'Add Member', 'wicket-acc' ); ?>
            </button>
        <?php endif; ?>
        <?php if ( ! $has_seats_available ) : ?>
            <div class="wt:mt-2 wt:p-3 wt:bg-yellow-50 wt:border wt:border-yellow-200 wt:rounded-md wt:text-yellow-800 wt:text-sm">
                <div class="wt:flex wt:items-center wt:gap-2">
                    <svg class="wt:w-5 wt:h-5 wt:text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <span><?php esc_html_e( 'All seats have been assigned. Please purchase additional seats to add more members.', 'wicket-acc' ); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

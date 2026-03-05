<?php

declare(strict_types=1);

it('keeps cascade add flow relationship-only with seat-capacity guard', function (): void {
    $strategy = file_get_contents(dirname(__DIR__, 3) . '/src/Services/Strategies/CascadeStrategy.php');

    expect($strategy)->toBeString()->not->toBeFalse();
    expect($strategy)->toContain('private function ensureSeatAvailability(string $membership_uuid, array $log_context)');
    expect($strategy)->toContain("\$active_seats = (int) (\$membership_data['data']['attributes']['active_assignments_count'] ?? 0);");
    expect($strategy)->toContain("return new \\WP_Error('seat_limit_reached', 'No seats available for this organization.');");
    expect($strategy)->not->toContain('wicket_assign_person_to_org_membership(');
    expect($strategy)->not->toContain('assignPersonToMembershipSeat(');
});

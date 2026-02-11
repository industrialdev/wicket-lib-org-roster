<?php

declare(strict_types=1);

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;

it('registers membership cycle strategy in strategy map', function (): void {
    $service = new MemberService(new ConfigService());

    $strategies = (function (): array {
        return $this->strategies;
    })->call($service);

    expect($strategies)->toHaveKey('membership_cycle');
});


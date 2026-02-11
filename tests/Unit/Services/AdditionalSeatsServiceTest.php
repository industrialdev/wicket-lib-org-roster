<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Services\AdditionalSeatsService;
use OrgManagement\Services\ConfigService;

if (!function_exists('wicket_api_client')) {
    function wicket_api_client()
    {
        return $GLOBALS['__orgroster_api_client'] ?? null;
    }
}

it('returns purchasable additional seats product id', function (): void {
    Functions\when('wc_get_product_id_by_sku')->alias(function (string $sku): int {
        return $sku === 'additional-seats' ? 99 : 0;
    });

    Functions\when('wc_get_product')->alias(function (int $product_id) {
        return new class {
            public function is_purchasable(): bool
            {
                return true;
            }
        };
    });

    $service = new AdditionalSeatsService(new ConfigService());

    expect($service->get_additional_seats_product())->toBe(99);
});

it('returns null when additional seats product is not purchasable', function (): void {
    Functions\when('wc_get_product_id_by_sku')->alias(function (string $sku): int {
        return $sku === 'additional-seats' ? 101 : 0;
    });

    Functions\when('wc_get_product')->alias(function (int $product_id) {
        return new class {
            public function is_purchasable(): bool
            {
                return false;
            }
        };
    });

    $service = new AdditionalSeatsService(new ConfigService());

    expect($service->get_additional_seats_product())->toBeNull();
});

it('returns false when membership id is empty for MDP update', function (): void {
    $service = new AdditionalSeatsService(new ConfigService());

    expect($service->update_mdp_membership_max_assignments('', 5))->toBeFalse();
});

it('updates MDP max assignments when API responds with data', function (): void {
    $patchCalls = [];
    $GLOBALS['__orgroster_api_client'] = new class($patchCalls) {
        public array $patchCalls = [];

        public function __construct(array &$patchCalls)
        {
            $this->patchCalls = &$patchCalls;
        }

        public function get(string $path)
        {
            return ['data' => ['id' => 'mem-1', 'attributes' => []]];
        }

        public function patch(string $path, array $options)
        {
            $this->patchCalls[] = ['path' => $path, 'options' => $options];

            return ['data' => ['id' => 'mem-1']];
        }
    };

    $service = new AdditionalSeatsService(new ConfigService());

    expect($service->update_mdp_membership_max_assignments('mem-1', 42))->toBeTrue()
        ->and($GLOBALS['__orgroster_api_client']->patchCalls)->toHaveCount(1)
        ->and($GLOBALS['__orgroster_api_client']->patchCalls[0]['path'])->toBe('organization_memberships/mem-1')
        ->and($GLOBALS['__orgroster_api_client']->patchCalls[0]['options']['json']['data']['attributes']['max_assignments'])->toBe(42);
});

it('returns false when MDP patch response is invalid', function (): void {
    $GLOBALS['__orgroster_api_client'] = new class {
        public function get(string $path)
        {
            return ['data' => ['id' => 'mem-1', 'attributes' => []]];
        }

        public function patch(string $path, array $options)
        {
            return [];
        }
    };

    $service = new AdditionalSeatsService(new ConfigService());

    expect($service->update_mdp_membership_max_assignments('mem-1', 7))->toBeFalse();
});

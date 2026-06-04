<?php

declare(strict_types=1);

return [
    'schema' => 'administering.runtime_scope.bundle_catalog.v1',
    'components' => [
        'accessing' => [
            'package' => 'accessing/access',
            'bundle' => App\Accessing\AccessingBundle::class,
        ],
        'administering' => [
            'package' => 'administering/admin',
            'bundle' => App\Administering\AdministeringBundle::class,
        ],
        'analysing' => [
            'package' => 'analysing/analytics',
            'bundle' => App\Analysing\AnalysingBundle::class,
        ],
        'applicating' => [
            'package' => 'applicating/application',
            'bundle' => App\Applicating\ApplicatingBundle::class,
        ],
        'attaching' => [
            'package' => 'attaching/attachment',
            'bundle' => App\Attaching\AttachingBundle::class,
        ],
        'billing' => [
            'package' => 'billing/billing',
            'bundle' => App\Billing\BillingBundle::class,
        ],
        'carting' => [
            'package' => 'carting/cart',
            'bundle' => App\Carting\CartingBundle::class,
        ],
        'cataloging' => [
            'package' => 'cataloging/catalog',
            'bundle' => App\Cataloging\CatalogingBundle::class,
        ],
        'commissioning' => [
            'package' => 'commissioning/commission',
            'bundle' => App\Commissioning\CommissioningBundle::class,
        ],
        'cruding' => [
            'package' => 'cruding/crud',
            'bundle' => App\Cruding\CrudingBundle::class,
        ],
        'domaining' => [
            'package' => 'domaining/domain',
            'bundle' => App\Domaining\DomainingBundle::class,
        ],
        'exchanging' => [
            'package' => 'exchanging/exchange',
            'bundle' => App\Exchanging\ExchangingBundle::class,
        ],
        'indexing' => [
            'package' => 'indexing/index',
            'bundle' => App\Indexing\IndexingBundle::class,
        ],
        'interfacing' => [
            'package' => 'interfacing/interface',
            'bundle' => App\Interfacing\InterfaceBundle::class,
        ],
        'localizing' => [
            'package' => 'localizing/locale',
            'bundle' => App\Localizing\LocalizingBundle::class,
        ],
        'managing' => [
            'package' => 'smart-responsor/managing',
            'bundle' => App\Managing\ManagingBundle::class,
        ],
        'merchandising' => [
            'package' => 'merchandising/merch',
            'bundle' => App\Merchandising\MerchandisingBundle::class,
        ],
        'messaging' => [
            'package' => 'messaging/message',
            'bundle' => App\Messaging\MessagingBundle::class,
        ],
        'navigating' => [
            'package' => 'navigating/navigation',
            'bundle' => App\Navigating\NavigationBundle::class,
        ],
        'ordering' => [
            'package' => 'ordering/order',
            'bundle' => App\Ordering\OrderingBundle::class,
        ],
        'paging' => [
            'package' => 'smart-responsor/paging',
            'bundle' => App\Paging\PageBundle::class,
        ],
        'paying' => [
            'package' => 'paying/payment',
            'bundle' => App\Paying\PayingBundle::class,
        ],
        'projecting' => [
            'package' => 'project/project',
            'bundle' => App\Projecting\ProjectingBundle::class,
        ],
        'rolling' => [
            'package' => 'rolling/role',
            'bundle' => App\Rolling\RollingBundle::class,
        ],
        'searching' => [
            'package' => 'searching/search',
            'bundle' => App\Searching\SearchingBundle::class,
        ],
        'shipping' => [
            'package' => 'shipping/shipment',
            'bundle' => App\Shipping\ShippingBundle::class,
        ],
        'subscripting' => [
            'package' => 'smart-responsor/subscription',
            'bundle' => App\Subscripting\Bundle\SubscriptionBundle::class,
        ],
        'tagging' => [
            'package' => 'tagging/tag',
            'bundle' => App\Tagging\TaggingBundle::class,
        ],
        'taxating' => [
            'package' => 'taxating/taxation',
            'bundle' => App\Taxating\TaxatingBundle::class,
        ],
        'vendoring' => [
            'package' => 'vendoring/vendor',
            'bundle' => App\Vendoring\VendoringBundle::class,
        ],
        'viewing' => [
            'package' => 'viewing/view',
            'bundle' => App\Viewing\ViewingBundle::class,
        ],
    ],
];

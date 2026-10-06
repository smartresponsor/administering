<?php

declare(strict_types=1);

namespace App\Administering;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers the Administering component for host Symfony compositions.
 *
 * Runtime behavior remains in typed Administering services/controllers; the bundle
 * is only the integration entry point when the package runs inside a host app.
 */
final class AdministeringBundle extends Bundle
{
}
